<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Validation;

use CloudCreativity\Modules\Toolkit\Pipeline\AccumulationProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactoryInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineInterface;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrorsInterface;

abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @var PipelineBuilderFactoryInterface
     */
    private readonly PipelineBuilderFactoryInterface $pipelineFactory;

    /**
     * @var iterable<string|callable>
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
        $this->pipelineFactory = PipelineBuilderFactory::make($pipelineFactory);
    }

    /**
     * @param iterable<string|callable> $rules
     * @return $this
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
            static function (?ListOfErrorsInterface $carry, ?ListOfErrorsInterface $errors): ListOfErrorsInterface {
                $errors ??= new ListOfErrors();
                return $carry ? $carry->merge($errors) : $errors;
            },
        );
    }
}
