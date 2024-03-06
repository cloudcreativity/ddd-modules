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

final class Pipeline implements PipelineInterface
{
    /**
     * @var ProcessorInterface
     */
    private readonly ProcessorInterface $processor;

    /**
     * Pipeline constructor.
     *
     * @param ProcessorInterface|null $processor
     * @param callable[] $stages
     */
    public function __construct(
        ?ProcessorInterface $processor,
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
    public function pipe(callable $stage): PipelineInterface
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
