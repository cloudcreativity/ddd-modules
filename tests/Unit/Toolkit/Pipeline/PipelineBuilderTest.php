<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Pipeline;

use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Processor;
use CloudCreativity\Modules\Toolkit\Pipeline\LazyPipe;
use CloudCreativity\Modules\Toolkit\Pipeline\Pipeline;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use PHPUnit\Framework\TestCase;

class PipelineBuilderTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $expected = new Pipeline(
            $processor = $this->createMock(Processor::class),
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
        $container = $this->createMock(PipeContainer::class);
        $processor = $this->createMock(Processor::class);

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
        $processor = $this->createMock(Processor::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot use a string pipe name without a pipe container.');

        (new PipelineBuilder())
            ->add('strtoupper')
            ->add('SomeService')
            ->add('strtolower')
            ->build($processor);
    }
}
