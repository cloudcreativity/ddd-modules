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

final class PipelineBuilderFactory implements PipelineBuilderFactoryInterface
{
    /**
     * @param PipelineBuilderFactoryInterface|PipeContainerInterface|null $factoryOrContainer
     * @return PipelineBuilderFactoryInterface
     */
    public static function make(
        PipelineBuilderFactoryInterface|PipeContainerInterface|null $factoryOrContainer,
    ): PipelineBuilderFactoryInterface {
        if ($factoryOrContainer instanceof PipelineBuilderFactoryInterface) {
            return $factoryOrContainer;
        }

        return new self($factoryOrContainer);
    }

    /**
     * PipelineBuilderFactory constructor
     *
     * @param PipeContainerInterface|null $container
     */
    public function __construct(private readonly ?PipeContainerInterface $container = null)
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
