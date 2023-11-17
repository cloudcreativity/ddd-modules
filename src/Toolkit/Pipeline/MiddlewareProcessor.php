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

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline;

use Closure;

class MiddlewareProcessor implements ProcessorInterface
{
    /**
     * @var Closure
     */
    private readonly Closure $destination;

    /**
     * Return a new middleware processor with a callable wrapped in a closure.
     *
     * @param callable $destination
     * @return MiddlewareProcessor
     */
    public static function wrap(callable $destination): self
    {
        return new self(static fn($passable) => $destination($passable));
    }

    /**
     * MiddlewareProcessor constructor.
     *
     * @param Closure|null $destination
     */
    public function __construct(?Closure $destination = null)
    {
        $this->destination = $destination ?? static fn($payload) => $payload;
    }

    /**
     * @inheritDoc
     */
    public function process(mixed $payload, callable ...$stages): mixed
    {
        $pipeline = array_reduce(
            array_reverse($stages), $this->carry(), $this->destination
        );

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
