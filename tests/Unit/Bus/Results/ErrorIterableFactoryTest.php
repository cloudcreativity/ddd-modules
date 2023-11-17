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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus\Results;

use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorFactoryInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorIterableFactory;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorIterableFactoryInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorIterableInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ListOfErrors;
use IteratorAggregate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ErrorIterableFactoryTest extends TestCase
{
    /**
     * @var ErrorIterableFactoryInterface
     */
    private ErrorIterableFactoryInterface $errorIterableFactory;

    /**
     * @var ErrorFactoryInterface&MockObject
     */
    private ErrorFactoryInterface $errorFactory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->errorIterableFactory = new ErrorIterableFactory(
            $this->errorFactory = $this->createMock(ErrorFactoryInterface::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        ErrorIterableFactory::setInstance(null);
    }

    public function testMakeWithErrors(): void
    {
        $this->errorFactory
            ->expects($this->never())
            ->method('make');

        $expected = $this->createMock(ErrorIterableInterface::class);

        $this->assertSame($expected, $this->errorIterableFactory->make($expected));
    }

    public function testMakeWithError(): void
    {
        $mock = $this->createMock(ErrorInterface::class);

        $this->errorFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($mock))
            ->willReturn($expected = $this->createMock(ErrorInterface::class));

        $actual = $this->errorIterableFactory->make($mock);

        $this->assertEquals(new ListOfErrors($expected), $actual);
    }

    public function testMakeWithIterable(): void
    {
        $iterable = new class implements IteratorAggregate
        {
            public function getIterator(): \Generator
            {
                yield from [
                    'one',
                    'two',
                    'three',
                ];
            }
        };

        $this->errorFactory
            ->expects($this->exactly(3))
            ->method('make')
            ->withConsecutive(['one'], ['two'], ['three'])
            ->willReturn($expected = $this->createMock(ErrorInterface::class));

        $actual = $this->errorIterableFactory->make($iterable);

        $this->assertEquals(new ListOfErrors($expected, $expected, $expected), $actual);
    }

    public function testMakeWithString(): void
    {
        $this->errorFactory
            ->expects($this->once())
            ->method('make')
            ->with($message = 'Boom!')
            ->willReturn($expected = $this->createMock(ErrorInterface::class));

        $actual = $this->errorIterableFactory->make($message);

        $this->assertEquals(new ListOfErrors($expected), $actual);
    }

    public function testSetInstance(): void
    {
        $mock = $this->createMock(ErrorIterableFactoryInterface::class);

        ErrorIterableFactory::setInstance($mock);

        $this->assertSame($mock, ErrorIterableFactory::getInstance());
    }
}
