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

readonly class PipelineBuilderFactory implements PipelineBuilderFactoryInterface
{
    /**
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface $factoryOrContainer
     * @return PipelineBuilderFactoryInterface
     */
    public static function cast(
        PipelineBuilderFactoryInterface|PipeContainerInterface $factoryOrContainer,
    ): PipelineBuilderFactoryInterface
    {
        if ($factoryOrContainer instanceof PipeContainerInterface) {
            return new self($factoryOrContainer);
        }

        return $factoryOrContainer;
    }

    /**
     * PipelineBuilderFactory constructor
     *
     * @param PipeContainerInterface|null $container
     */
    public function __construct(private ?PipeContainerInterface $container = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function getPipelineBuilder(): PipelineBuilderInterface
    {
        return new PipelineBuilder($this->container);
    }
}
