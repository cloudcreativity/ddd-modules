<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus\Validation;

use CloudCreativity\Modules\Bus\Validation\CommandValidator;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use PHPUnit\Framework\TestCase;

class CommandValidatorTest extends TestCase
{
    /**
     * @return void
     */
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

        $b = function ($actual) use ($command): ?ListOfErrors {
            $this->assertSame($command, $actual);
            return null;
        };

        $c = function ($actual) use ($command, $error2, $error3): ListOfErrors {
            $this->assertSame($command, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $validator = new CommandValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b, $c])
            ->validate($command);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    /**
     * @return void
     */
    public function testNoRules(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $validator = new CommandValidator(new PipelineBuilderFactory());

        $this->assertEquals(new ListOfErrors(), $validator->validate($command));
    }
}
