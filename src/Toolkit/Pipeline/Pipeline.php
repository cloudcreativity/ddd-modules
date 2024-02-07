<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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
