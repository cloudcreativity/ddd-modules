<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Infrastructure;
use CloudCreativity\BalancedEvent\Common\Infrastructure\InfrastructureException;
use PHPUnit\Framework\TestCase;

class InfrastructureTest extends TestCase
{
    /**
     * @return void
     */
    public function testItDoesNotThrowWhenPreconditionIsTrue(): void
    {
        Infrastructure::assert(true, 'Unexpected message.');

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testItThrowsWhenPreconditionIsFalse(): void
    {
        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage($expected = 'Something was wrong.');

        Infrastructure::assert(false, $expected);
    }
}
