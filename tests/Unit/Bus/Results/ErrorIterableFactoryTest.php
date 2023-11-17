<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
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
