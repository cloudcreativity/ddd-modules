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

use AssertionError;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\GuidTypeMap;
use PHPUnit\Framework\TestCase;

class GuidTypeMapTest extends TestCase
{
    /**
     * @return GuidTypeMap
     */
    public function testItReturnsExpectedType(): GuidTypeMap
    {
        $map = new GuidTypeMap([
            'SomeTypeA' => 'SomeOtherTypeA',
            'SomeTypeB' => 'SomeOtherTypeB',
            'SomeTypeC' => 'SomeOtherTypeC',
            'NotString' => true,
            'EmptyString' => '',
        ]);

        $this->assertSame('SomeOtherTypeA', $map->typeFor('SomeTypeA'));
        $this->assertSame('SomeOtherTypeB', $map->typeFor('SomeTypeB'));
        $this->assertSame('SomeOtherTypeC', $map->typeFor('SomeTypeC'));

        return $map;
    }

    /**
     * @param GuidTypeMap $map
     * @return void
     * @depends testItReturnsExpectedType
     */
    public function testItReturnsExpectedGuid(GuidTypeMap $map): void
    {
        $this->assertEquals(
            Guid::fromInteger('SomeOtherTypeA', 99),
            $map->guidFor('SomeTypeA', 99),
        );

        $this->assertEquals(
            Guid::fromString('SomeOtherTypeC', 'foobar'),
            $map->guidFor('SomeTypeC', 'foobar'),
        );
    }

    /**
     * @param GuidTypeMap $map
     * @return void
     * @depends testItReturnsExpectedType
     */
    public function testItThrowsIfTypeIsNotValidString(GuidTypeMap $map): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expecting type for alias "NotString" to be a non-empty string.');

        $map->typeFor('NotString');
    }

    /**
     * @param GuidTypeMap $map
     * @return void
     * @depends testItReturnsExpectedType
     */
    public function testItThrowsIfTypeIsEmptyString(GuidTypeMap $map): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expecting type for alias "EmptyString" to be a non-empty string.');

        $map->typeFor('EmptyString');
    }

    /**
     * @param GuidTypeMap $map
     * @return void
     * @depends testItReturnsExpectedType
     */
    public function testItThrowsIfTypeIsNotDefined(GuidTypeMap $map): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Alias "NotDefined" is not defined in the type map.');

        $map->typeFor('NotDefined');
    }
}