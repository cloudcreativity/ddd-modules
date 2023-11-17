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

class AccumulationProcessor implements ProcessorInterface
{
    /**
     * @var callable
     */
    private $accumulator;

    /**
     * @var mixed|null
     */
    private mixed $initialValue;

    /**
     * AccumulationProcessor
     *
     * @param callable $accumulator
     * @param mixed|null $initialValue
     */
    public function __construct(callable $accumulator, mixed $initialValue = null)
    {
        $this->accumulator = $accumulator;
        $this->initialValue = $initialValue;
    }

    /**
     * @inheritDoc
     */
    public function process(mixed $payload, callable ...$stages): mixed
    {
        $result = $this->initialValue;

        foreach ($stages as $stage) {
            $result = ($this->accumulator)($result, $stage($payload));
        }

        return $result;
    }
}
