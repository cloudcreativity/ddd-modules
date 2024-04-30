<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\UnitOfWork;

use Closure;
use CloudCreativity\Modules\Application\Ports\Driven\Exceptions\ExceptionReporter;
use CloudCreativity\Modules\Application\Ports\Driven\UnitOfWork\UnitOfWork;
use RuntimeException;
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
     * @param UnitOfWork $unitOfWork
     * @param ExceptionReporter|null $reporter
     */
    public function __construct(
        private readonly UnitOfWork $unitOfWork,
        private readonly ?ExceptionReporter $reporter = null,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Closure $callback, int $attempts = 1): mixed
    {
        if ($this->active) {
            throw new RuntimeException(
                'Not expecting unit of work manager to start a unit of work within an existing one.',
            );
        }

        if ($attempts < 1) {
            throw new RuntimeException('Attempts must be greater than zero.');
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
            throw new RuntimeException(
                'Cannot queue a before commit callback as unit of work has been committed.',
            );
        }

        throw new RuntimeException('Cannot queue a before commit callback when not executing a unit of work.');
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

        throw new RuntimeException('Cannot queue an after commit callback when not executing a unit of work.');
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
