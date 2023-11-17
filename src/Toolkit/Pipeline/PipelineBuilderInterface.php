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

interface PipelineBuilderInterface
{
    /**
     * Add the provided stage.
     *
     * @param callable|string $stage
     * @return $this
     */
    public function add(callable|string $stage): self;

    /**
     * Add the provided stages.
     *
     * @param iterable $stages
     * @return $this
     */
    public function through(iterable $stages): self;

    /**
     * Build a new pipeline.
     *
     * @param ProcessorInterface|null $processor
     * @return PipelineInterface
     */
    public function build(ProcessorInterface $processor = null): PipelineInterface;
}
