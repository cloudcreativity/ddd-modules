<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use Closure;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer as IPipeContainer;
use Psr\Container\ContainerInterface;

final class PsrPipeContainer implements IPipeContainer
{
    /**
     * @var array<string,Closure>
     */
    private array $pipes = [];

    public function __construct(private readonly ?ContainerInterface $container = null)
    {
    }

    /**
     * Bind a pipe into the container.
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
            assert(is_callable($pipe), "Expecting pipe {$pipeName} from factory to be callable.");
            return $pipe;
        }

        if ($this->container) {
            $pipe = $this->container->get($pipeName);
            assert(is_callable($pipe), "Expecting pipe {$pipeName} from PSR container to be callable.");
            return $pipe;
        }

        throw new BusException('Unrecognised pipe name: ' . $pipeName);
    }
}
