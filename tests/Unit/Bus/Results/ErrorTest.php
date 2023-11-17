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
use CloudCreativity\BalancedEvent\Common\Bus\Results\ErrorInterface;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function test(): void
    {
        $error = new Error('foo', 'Bar', 10);

        $this->assertInstanceOf(ErrorInterface::class, $error);
        $this->assertSame('foo', $error->key());
        $this->assertSame('Bar', $error->message());
        $this->assertSame('Bar', (string) $error);
        $this->assertSame(10, $error->code());
        $this->assertSame([
            'key' => 'foo',
            'message' => 'Bar',
            'code' => 10,
        ], $error->context());
    }

    public function testWithoutKeyAndCode(): void
    {
        $error = new Error(null, 'Hello World');

        $this->assertNull($error->key());
        $this->assertSame('Hello World', $error->message());
        $this->assertNull($error->code());
        $this->assertSame([
            'message' => 'Hello World',
        ], $error->context());
    }
}
