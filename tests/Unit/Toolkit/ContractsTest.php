<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use CloudCreativity\BalancedEvent\Common\Toolkit\Contracts;
use PHPUnit\Framework\TestCase;

class ContractsTest extends TestCase
{
    public function testItDoesNotThrowWhenPreconditionIsTrue(): void
    {
        Contracts::assert(true, 'Not expected error.');
        $this->assertTrue(true);
    }

    public function testItThrowsWhenPreconditionIsFalse(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage($expected = 'The expected message.');

        Contracts::assert(false, $expected);
    }
}
