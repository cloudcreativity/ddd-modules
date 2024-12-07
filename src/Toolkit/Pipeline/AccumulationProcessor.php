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

use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Processor;

final class AccumulationProcessor implements Processor
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
