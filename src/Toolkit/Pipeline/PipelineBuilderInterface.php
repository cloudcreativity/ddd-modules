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
     * @param iterable<string|callable> $stages
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
