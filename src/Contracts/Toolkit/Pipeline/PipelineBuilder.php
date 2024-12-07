<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Pipeline;

interface PipelineBuilder
{
    /**
     * Add the provided stage.
     *
     * @param callable|string $stage
     * @return $this
     */
    public function add(callable|string $stage): static;

    /**
     * Add the provided stages.
     *
     * @param iterable<string|callable> $stages
     * @return $this
     */
    public function through(iterable $stages): static;

    /**
     * Build a new pipeline.
     *
     * @param Processor|null $processor
     * @return Pipeline
     */
    public function build(?Processor $processor = null): Pipeline;
}
