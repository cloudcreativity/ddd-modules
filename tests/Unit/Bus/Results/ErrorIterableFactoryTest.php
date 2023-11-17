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

namespace CloudCreativity\Modules\Tests\Unit\Bus\Results;

use CloudCreativity\Modules\Bus\Results\ErrorFactoryInterface;
use CloudCreativity\Modules\Bus\Results\ErrorInterface;
use CloudCreativity\Modules\Bus\Results\ErrorIterableFactory;
use CloudCreativity\Modules\Bus\Results\ErrorIterableFactoryInterface;
use CloudCreativity\Modules\Bus\Results\ErrorIterableInterface;
use CloudCreativity\Modules\Bus\Results\ListOfErrors;
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
        $expected1 = $this->createMock(ErrorInterface::class);
        $expected2 = $this->createMock(ErrorInterface::class);
        $expected3 = $this->createMock(ErrorInterface::class);

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
            ->willReturnCallback(fn ($message) => match ($message) {
                'one' => $expected1,
                'two' => $expected2,
                'three' => $expected3,
            });

        $actual = $this->errorIterableFactory->make($iterable);

        $this->assertEquals(new ListOfErrors($expected1, $expected2, $expected3), $actual);
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
