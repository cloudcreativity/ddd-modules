<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
