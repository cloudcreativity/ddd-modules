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
