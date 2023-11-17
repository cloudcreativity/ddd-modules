<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Pipeline;

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\LazyPipe;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\Pipeline;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class PipelineBuilderTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $expected = new Pipeline(
            $processor = $this->createMock(ProcessorInterface::class),
            $stages = ['strtoupper', 'strtolower'],
        );

        $actual = (new PipelineBuilder())
            ->add($stages[0])
            ->add($stages[1])
            ->build($processor);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     */
    public function testServiceString(): void
    {
        $container = $this->createMock(PipeContainerInterface::class);
        $processor = $this->createMock(ProcessorInterface::class);

        $expected = new Pipeline($processor, [
            'strtoupper',
            new LazyPipe($container, 'SomeService'),
            'strtolower',
        ]);

        $actual = (new PipelineBuilder($container))
            ->through(['strtoupper', 'SomeService', 'strtolower'])
            ->build($processor);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     */
    public function testServiceStringWithoutContainer(): void
    {
        $processor = $this->createMock(ProcessorInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot use a string pipe name without a pipe container.');

        (new PipelineBuilder())
            ->add('strtoupper')
            ->add('SomeService')
            ->add('strtolower')
            ->build($processor);
    }
}
