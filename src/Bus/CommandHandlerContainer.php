<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Bus;

use Closure;
use RuntimeException;

class CommandHandlerContainer implements CommandHandlerContainerInterface
{
    /**
     * @var array<string,Closure>
     */
    private array $bindings = [];

    /**
     * Bind a command handler into the container.
     *
     * @param string $commandClass
     * @param Closure $binding
     * @return void
     */
    public function bind(string $commandClass, Closure $binding): void
    {
        $this->bindings[$commandClass] = $binding;
    }

    /**
     * @inheritDoc
     */
    public function get(string $commandClass): CommandHandlerInterface
    {
        $factory = $this->bindings[$commandClass] ?? null;

        if ($factory) {
            $innerHandler = $factory();
            assert(is_object($innerHandler), "Command handler binding for {$commandClass} must return an object.");
            return new CommandHandler($innerHandler);
        }

        throw new RuntimeException('No command handler bound for command class: ' . $commandClass);
    }
}