<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Persistence;

use Closure;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use CloudCreativity\Modules\Infrastructure\Log\ExceptionReporterInterface;
use Throwable;

final class UnitOfWorkManager implements UnitOfWorkManagerInterface
{
    /**
     * @var callable[]
     */
    private array $beforeCommit = [];

    /**
     * @var callable[]
     */
    private array $afterCommit = [];

    /**
     * @var bool
     */
    private bool $active = false;

    /**
     * @var bool
     */
    private bool $committed = false;

    /**
     * UnitOfWorkManager constructor.
     *
     * @param UnitOfWorkInterface $unitOfWork
     * @param ExceptionReporterInterface|null $reporter
     */
    public function __construct(
        private readonly UnitOfWorkInterface $unitOfWork,
        private readonly ?ExceptionReporterInterface $reporter = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Closure $callback, int $attempts = 1): mixed
    {
        if ($this->active) {
            throw new InfrastructureException(
                'Not expecting unit of work manager to start a unit of work within an existing one.',
            );
        }

        if ($attempts < 1) {
            throw new InfrastructureException('Attempts must be greater than zero.');
        }

        return $this->retry($callback, $attempts);
    }

    /**
     * @param Closure $callback
     * @param int $attempts
     * @return mixed
     */
    private function retry(Closure $callback, int $attempts): mixed
    {
        try {
            return $this->transaction($callback);
        } catch (Throwable $ex) {
            if ($attempts === 1) {
                throw $ex;
            }

            // Report "swallowed" exceptions.
            $this->reporter?->report($ex);
        }

        return $this->retry($callback, $attempts - 1);
    }

    /**
     * @param Closure $callback
     * @return mixed
     */
    private function transaction(Closure $callback): mixed
    {
        try {
            $result = $this->unitOfWork->execute(function () use ($callback) {
                $this->active = true;
                $value = $callback();
                $this->executeBeforeCommit();
                return $value;
            });
            $this->committed = true;
            $this->executeAfterCommit();
            return $result;
        } finally {
            $this->active = false;
            $this->committed = false;
            $this->beforeCommit = [];
            $this->afterCommit = [];
        }
    }

    /**
     * @inheritDoc
     */
    public function beforeCommit(callable $callback): void
    {
        if ($this->active && !$this->committed) {
            $this->beforeCommit[] = $callback;
            return;
        }

        if ($this->committed) {
            throw new InfrastructureException(
                'Cannot queue a before commit callback as unit of work has been committed.',
            );
        }

        throw new InfrastructureException('Cannot queue a before commit callback when not executing a unit of work.');
    }

    /**
     * @inheritDoc
     */
    public function afterCommit(callable $callback): void
    {
        if ($this->active) {
            $this->afterCommit[] = $callback;
            return;
        }

        throw new InfrastructureException('Cannot queue an after commit callback when not executing a unit of work.');
    }

    /**
     * @return void
     */
    private function executeBeforeCommit(): void
    {
        while($callback = array_shift($this->beforeCommit)) {
            $callback();
        }
    }

    /**
     * @return void
     */
    private function executeAfterCommit(): void
    {
        while($callback = array_shift($this->afterCommit)) {
            $callback();
        }
    }
}
