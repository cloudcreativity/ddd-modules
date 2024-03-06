<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Pipeline;

use Closure;
use RuntimeException;

final class PipeContainer implements PipeContainerInterface
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
