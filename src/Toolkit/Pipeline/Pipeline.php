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

class Pipeline implements PipelineInterface
{
    /**
     * Pipeline constructor.
     *
     * @param ProcessorInterface|null $processor
     * @param callable[] $stages
     */
    public function __construct(
        private ?ProcessorInterface $processor,
        private array $stages
    ) {
        $this->processor ??= new SimpleProcessor();
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
