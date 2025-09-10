<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Pipeline;

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use RuntimeException;

final class PipeContainer implements IPipeContainer
{
    /**
     * @var array<string,Closure>
     */
    private array $pipes = [];

    /**
     * Bind a pipe into the container.
     *
     */
    public function bind(string $pipeName, Closure $factory): void
    {
        $this->pipes[$pipeName] = $factory;
    }

    public function get(string $pipeName): callable
    {
        $factory = $this->pipes[$pipeName] ?? null;

        if (is_callable($factory)) {
            $pipe = $factory();
            assert(is_callable($pipe), 'Expecting pipe from factory to be callable.');
            return $pipe;
        }

        throw new RuntimeException('Unrecognised pipe name: ' . $pipeName);
    }
}
