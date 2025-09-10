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

use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Pipeline as IPipeline;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Processor;

final class Pipeline implements IPipeline
{
    private readonly Processor $processor;

    /**
     * @param callable[] $stages
     */
    public function __construct(
        ?Processor $processor,
        private array $stages,
    ) {
        $this->processor = $processor ?? new SimpleProcessor();
    }

    public function __invoke(mixed $payload): mixed
    {
        return $this->process($payload);
    }

    public function pipe(callable $stage): Pipeline
    {
        $pipeline = clone $this;
        $pipeline->stages[] = $stage;

        return $pipeline;
    }

    public function process(mixed $payload): mixed
    {
        return $this->processor->process($payload, ...$this->stages);
    }
}
