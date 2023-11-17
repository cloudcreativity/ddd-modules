<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Identifiers;

use CloudCreativity\BalancedEvent\Common\Toolkit\ContractException;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\LazyListOfGuids;
use PHPUnit\Framework\TestCase;

class LazyListOfGuidsTest extends TestCase
{
    /**
     * @return void
     * @throws \Exception
     */
    public function testOfOneTypeDoesNotThrowWhenItHasTheExpectedType(): void
    {
        $expected = [
            Guid::fromInteger('SomeType', 1),
            Guid::fromInteger('SomeType', 2),
            Guid::fromInteger('SomeType', 3),
        ];

        $guids = new LazyListOfGuids(function () use ($expected) {
            yield from $expected;
        });

        $actual = iterator_to_array($guids->ofOneType('SomeType'));

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testOfOneTypeThrowsWhenItIsNot(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Expecting GUIDs of type "SomeType", found "SomeOtherType".');

        $guids = new LazyListOfGuids(function () {
            yield Guid::fromInteger('SomeType', 1);
            yield Guid::fromInteger('SomeType', 2);
            yield Guid::fromInteger('SomeOtherType', 3);
        });

        iterator_to_array($guids->ofOneType('SomeType'));
    }
}
