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

class InterruptibleProcessor implements ProcessorInterface
{
    /**
     * @var callable
     */
    private $check;

    /**
     * InterruptibleProcessor constructor.
     *
     * @param callable $check
     */
    public function __construct(callable $check)
    {
        $this->check = $check;
    }

    /**
     * @inheritDoc
     */
    public function process(mixed $payload, callable ...$stages): mixed
    {
        foreach ($stages as $stage) {
            $payload = $stage($payload);

            if (true !== ($this->check)($payload)) {
                return $payload;
            }
        }

        return $payload;
    }
}
