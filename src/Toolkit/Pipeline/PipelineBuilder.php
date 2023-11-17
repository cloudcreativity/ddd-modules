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

use RuntimeException;

class PipelineBuilder implements PipelineBuilderInterface
{
    /**
     * @var callable[]
     */
    private array $stages = [];

    /**
     * PipelineBuilder constructor.
     *
     * @param PipeContainerInterface|null $container
     */
    public function __construct(private readonly ?PipeContainerInterface $container = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function add(callable|string $stage): PipelineBuilderInterface
    {
        $this->stages[] = $this->normalize($stage);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function through(iterable $stages): PipelineBuilderInterface
    {
        foreach ($stages as $stage) {
            $this->add($stage);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ProcessorInterface $processor = null): PipelineInterface
    {
        return new Pipeline($processor, $this->stages);
    }

    /**
     * @param callable|string $stage
     * @return callable
     */
    private function normalize(callable|string $stage): callable
    {
        if (is_callable($stage)) {
            return $stage;
        }

        if (is_string($stage) && $this->container) {
            return new LazyPipe($this->container, $stage);
        }

        throw new RuntimeException('Cannot use a string pipe name without a pipe container.');
    }
}
