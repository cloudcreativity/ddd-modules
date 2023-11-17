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

use CloudCreativity\BalancedEvent\Common\Bus\Results\Error;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorFactory;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorFactoryInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorInterface;
use PHPUnit\Framework\TestCase;

class ErrorFactoryTest extends TestCase
{
    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        ErrorFactory::setInstance(null);
    }

    public function testMakeWithError(): void
    {
        $expected = $this->createMock(ErrorInterface::class);

        $this->assertSame($expected, ErrorFactory::getInstance()->make($expected));
    }

    public function testMakeWithString(): void
    {
        $expected = new Error(null, 'Boom!');

        $actual = ErrorFactory::getInstance()->make($expected->message());

        $this->assertEquals($expected, $actual);
    }

    public function testSetInstance(): void
    {
        $mock = $this->createMock(ErrorFactoryInterface::class);

        ErrorFactory::setInstance($mock);

        $this->assertSame($mock, ErrorFactory::getInstance());
    }
}
