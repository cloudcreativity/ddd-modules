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
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Processor;

final readonly class MiddlewareProcessor implements Processor
{
    private Closure $destination;

    /**
     * Return a new middleware processor that calls the destination and returns the result.
     *
     */
    public static function wrap(callable $destination): self
    {
        return new self(static fn (mixed $passable) => $destination($passable));
    }

    /**
     * Return a new middleware processor that calls the destination without returning a result.
     *
     */
    public static function call(callable $destination): self
    {
        return new self(static function (mixed $passable) use ($destination): void {
            $destination($passable);
        });
    }

    /**
     * MiddlewareProcessor constructor.
     *
     */
    public function __construct(?Closure $destination = null)
    {
        $this->destination = $destination ?? static fn ($payload) => $payload;
    }

    public function process(mixed $payload, callable ...$stages): mixed
    {
        $pipeline = array_reduce(
            array_reverse($stages),
            $this->carry(),
            $this->destination,
        );

        assert(is_callable($pipeline));

        return $pipeline($payload);
    }

    private function carry(): Closure
    {
        return function ($stack, callable $stage): Closure {
            return function ($passable) use ($stack, $stage) {
                return $stage($passable, $stack);
            };
        };
    }
}
