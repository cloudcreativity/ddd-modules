<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline;

use Closure;
use RuntimeException;

class PipeContainer implements PipeContainerInterface
{
    /**
     * @var array<string,Closure>
     */
    private array $pipes = [];

    /**
     * Bind a pipe into the container.
     *
     * @param string $pipeName
     * @param Closure $factory
     * @return void
     */
    public function bind(string $pipeName, Closure $factory): void
    {
        $this->pipes[$pipeName] = $factory;
    }

    /**
     * @inheritDoc
     */
    public function get(string $pipeName): callable
    {
        $factory = $this->pipes[$pipeName] ?? null;

        if ($factory) {
            return $factory();
        }

        throw new RuntimeException('Unrecognised pipe name: ' . $pipeName);
    }
}