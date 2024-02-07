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

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Pipeline;

use CloudCreativity\Modules\Toolkit\Pipeline\LazyPipe;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LazyPipeTest extends TestCase
{
    /**
     * @var PipeContainerInterface&MockObject
     */
    private PipeContainerInterface $container;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(PipeContainerInterface::class);
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
            ->willReturn($pipe = $this->createMock(PipelineInterface::class));

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
