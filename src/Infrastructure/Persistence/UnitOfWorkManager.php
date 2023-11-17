<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Infrastructure\Persistence;

use Closure;
use CloudCreativity\BalancedEvent\Common\Infrastructure\InfrastructureException;

class UnitOfWorkManager implements UnitOfWorkManagerInterface
{
    /**
     * @var UnitOfWorkInterface
     */
    private UnitOfWorkInterface $unitOfWork;

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
     * @param UnitOfWorkInterface $transaction
     */
    public function __construct(UnitOfWorkInterface $transaction)
    {
        $this->unitOfWork = $transaction;
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

        try {
            $result = $this->unitOfWork->execute(function () use ($callback) {
                $this->active = true;
                $value = $callback();
                $this->executeBeforeCommit();
                return $value;
            }, $attempts);
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
