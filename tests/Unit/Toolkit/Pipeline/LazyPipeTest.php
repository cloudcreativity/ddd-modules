<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Pipeline;

use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Pipeline;
use CloudCreativity\Modules\Toolkit\Pipeline\LazyPipe;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LazyPipeTest extends TestCase
{
    /**
     * @var PipeContainer&MockObject
     */
    private PipeContainer $container;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(PipeContainer::class);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('SomePipe')
            ->willReturn($pipe = $this->createMock(Pipeline::class));

        $pipe
            ->expects($this->once())
            ->method('__invoke')
            ->with('arg1', 'arg2')
            ->willReturn($expected = 'Hello World!');

        $lazyPipe = new LazyPipe($this->container, 'SomePipe');
        $actual = $lazyPipe('arg1', 'arg2');

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItRethrowsException(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('PipeThatWillError')
            ->willThrowException($expected = new \LogicException('Boom!'));

        $lazyPipe = new LazyPipe($this->container, 'PipeThatWillError');

        try {
            $lazyPipe('blah');
            $this->fail('No exception thrown.');
        } catch (\RuntimeException $ex) {
            $this->assertSame('Failed to get pipe "PipeThatWillError" from container.', $ex->getMessage());
            $this->assertSame(0, $ex->getCode());
            $this->assertSame($expected, $ex->getPrevious());
        }
    }
}
