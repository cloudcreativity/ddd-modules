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

interface Pipeline
{
    /**
     * Process the payload.
     *
     * @param mixed $payload
     * @return mixed
     */
    public function __invoke(mixed $payload): mixed;

    /**
     * Create a new pipeline with the appended stage.
     *
     * @param callable $stage
     * @return Pipeline
     */
    public function pipe(callable $stage): self;

    /**
     * Process the payload through the pipeline.
     *
     * @param mixed $payload
     * @return mixed
     */
    public function process(mixed $payload): mixed;
}
