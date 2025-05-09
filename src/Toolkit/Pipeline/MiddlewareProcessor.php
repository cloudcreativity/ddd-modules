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

final class MiddlewareProcessor implements Processor
{
    /**
     * @var Closure
     */
    private readonly Closure $destination;

    /**
     * Return a new middleware processor that calls the destination and returns the result.
     *
     * @param callable $destination
     * @return MiddlewareProcessor
     */
    public static function wrap(callable $destination): self
    {
        return new self(static fn (mixed $passable) => $destination($passable));
    }

    /**
     * Return a new middleware processor that calls the destination without returning a result.
     *
     * @param callable $destination
     * @return MiddlewareProcessor
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
     * @param Closure|null $destination
     */
    public function __construct(?Closure $destination = null)
    {
        $this->destination = $destination ?? static fn ($payload) => $payload;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @return Closure
     */
    private function carry(): Closure
    {
        return function ($stack, callable $stage): Closure {
            return function ($passable) use ($stack, $stage) {
                return $stage($passable, $stack);
            };
        };
    }
}
