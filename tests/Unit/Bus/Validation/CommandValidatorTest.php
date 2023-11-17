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

use CloudCreativity\BalancedEvent\Common\Bus\CommandInterface;
use CloudCreativity\BalancedEvent\Common\Bus\Results\Error;
use CloudCreativity\BalancedEvent\Common\Bus\Results\KeyedSetOfErrors;
use CloudCreativity\BalancedEvent\Common\Bus\Results\ListOfErrors;
use CloudCreativity\BalancedEvent\Common\Bus\Validation\CommandValidator;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineBuilderFactory;
use PHPUnit\Framework\TestCase;

class CommandValidatorTest extends TestCase
{
    public function test(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $error1 = new Error(null, 'Message 1');
        $error2 = new Error(null, 'Message 2');
        $error3 = new Error(null, 'Message 3');

        $a = function ($actual) use ($command, $error1): ListOfErrors {
            $this->assertSame($command, $actual);
            return new ListOfErrors($error1);
        };

        $b = function ($actual) use ($command, $error2, $error3): KeyedSetOfErrors {
            $this->assertSame($command, $actual);
            return new KeyedSetOfErrors($error2, $error3);
        };

        $validator = new CommandValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b])
            ->validate($command);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    public function testKeyedSet(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $error1 = new Error(null, 'Message 1');
        $error2 = new Error(null, 'Message 2');
        $error3 = new Error(null, 'Message 3');

        $a = function ($actual) use ($command, $error1): KeyedSetOfErrors {
            $this->assertSame($command, $actual);
            return new KeyedSetOfErrors($error1);
        };

        $b = function ($actual) use ($command, $error2, $error3): ListOfErrors {
            $this->assertSame($command, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $validator = new CommandValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b])
            ->validate($command);

        $this->assertInstanceOf(KeyedSetOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    public function testNoRules(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $validator = new CommandValidator(new PipelineBuilderFactory(), []);

        $this->assertEquals(new ListOfErrors(), $validator->validate($command));
    }
}
