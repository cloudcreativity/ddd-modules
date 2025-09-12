<?php

/*
 * Copyright 2025 Cloud Creativity Limited
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
     * @return $this
     */
    public function add(callable|string $stage): static;

    /**
     * Add the provided stages.
     *
     * @param iterable<callable|string> $stages
     * @return $this
     */
    public function through(iterable $stages): static;

    /**
     * Build a new pipeline.
     */
    public function build(?Processor $processor = null): Pipeline;
}
