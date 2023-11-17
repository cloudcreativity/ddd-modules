<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Bus\Validation;

use CloudCreativity\BalancedEvent\Common\Bus\QueryInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\Error;
use CloudCreativity\BalancedEvent\Common\Bus\Results\KeyedSetOfErrors;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ListOfErrors;
use CloudCreativity\BalancedEvent\Common\Bus\Validation\QueryValidator;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactory;
use PHPUnit\Framework\TestCase;

class QueryValidatorTest extends TestCase
{
    public function test(): void
    {
        $query = $this->createMock(QueryInterface::class);
        $error1 = new Error(null, 'Message 1');
        $error2 = new Error(null, 'Message 2');
        $error3 = new Error(null, 'Message 3');

        $a = function ($actual) use ($query, $error1): ListOfErrors {
            $this->assertSame($query, $actual);
            return new ListOfErrors($error1);
        };

        $b = function ($actual) use ($query, $error2, $error3): KeyedSetOfErrors {
            $this->assertSame($query, $actual);
            return new KeyedSetOfErrors($error2, $error3);
        };

        $validator = new QueryValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b])
            ->validate($query);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    public function testKeyedSet(): void
    {
        $query = $this->createMock(QueryInterface::class);
        $error1 = new Error(null, 'Message 1');
        $error2 = new Error(null, 'Message 2');
        $error3 = new Error(null, 'Message 3');

        $a = function ($actual) use ($query, $error1): KeyedSetOfErrors {
            $this->assertSame($query, $actual);
            return new KeyedSetOfErrors($error1);
        };

        $b = function ($actual) use ($query, $error2, $error3): ListOfErrors {
            $this->assertSame($query, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $validator = new QueryValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b])
            ->validate($query);

        $this->assertInstanceOf(KeyedSetOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    public function testNoRules(): void
    {
        $query = $this->createMock(QueryInterface::class);
        $validator = new QueryValidator(new PipelineBuilderFactory(), []);

        $this->assertEquals(new ListOfErrors(), $validator->validate($query));
    }
}
