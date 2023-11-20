<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Bus\Validation;

use CloudCreativity\Modules\Bus\CommandInterface;
use CloudCreativity\Modules\Bus\Results\Error;
use CloudCreativity\Modules\Bus\Results\ListOfErrors;
use CloudCreativity\Modules\Bus\Validation\CommandValidator;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
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

        $b = function ($actual) use ($command, $error2, $error3): ListOfErrors {
            $this->assertSame($command, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $validator = new CommandValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b])
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
