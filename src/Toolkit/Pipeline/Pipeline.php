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
    /**
     * @var Processor
     */
    private readonly Processor $processor;

    /**
     * Pipeline constructor.
     *
     * @param Processor|null $processor
     * @param callable[] $stages
     */
    public function __construct(
        ?Processor $processor,
        private array $stages,
    ) {
        $this->processor = $processor ?? new SimpleProcessor();
    }

    /**
     * @inheritDoc
     */
    public function __invoke(mixed $payload): mixed
    {
        return $this->process($payload);
    }

    /**
     * @inheritDoc
     */
    public function pipe(callable $stage): Pipeline
    {
        $pipeline = clone $this;
        $pipeline->stages[] = $stage;

        return $pipeline;
    }

    /**
     * @inheritDoc
     */
    public function process(mixed $payload): mixed
    {
        return $this->processor->process($payload, ...$this->stages);
    }
}
