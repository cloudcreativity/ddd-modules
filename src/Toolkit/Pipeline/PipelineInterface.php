<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Pipeline;

interface PipelineInterface
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
     * @return PipelineInterface
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
