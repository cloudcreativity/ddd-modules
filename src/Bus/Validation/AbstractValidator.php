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

namespace CloudCreativity\BalancedEvent\Common\Bus\Validation;

use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorIterableInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\AccumulationProcessor;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactoryInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineInterface;

abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @var PipelineBuilderFactoryInterface
     */
    private readonly PipelineBuilderFactoryInterface $pipelineFactory;

    /**
     * @var iterable
     */
    private iterable $rules = [];

    /**
     * AbstractValidator constructor
     *
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface $pipelineFactory
     */
    public function __construct(
        PipelineBuilderFactoryInterface|PipeContainerInterface $pipelineFactory = new PipelineBuilderFactory(),
    ) {
        $this->pipelineFactory = PipelineBuilderFactory::cast($pipelineFactory);
    }

    /**
     * @inheritDoc
     */
    public function using(iterable $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return PipelineInterface
     */
    protected function getPipeline(): PipelineInterface
    {
        return $this->pipelineFactory
            ->getPipelineBuilder()
            ->through($this->rules)
            ->build($this->processor());
    }

    /**
     * @return AccumulationProcessor
     */
    private function processor(): AccumulationProcessor
    {
        return new AccumulationProcessor(
            static fn(?ErrorIterableInterface $carry, ErrorIterableInterface $errors): ErrorIterableInterface =>
                $carry ? $carry->merge($errors) : $errors,
        );
    }
}
