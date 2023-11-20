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

use CloudCreativity\Modules\Bus\QueryInterface;
use CloudCreativity\Modules\Bus\Results\Error;
use CloudCreativity\Modules\Bus\Results\ListOfErrors;
use CloudCreativity\Modules\Bus\Validation\QueryValidator;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use PHPUnit\Framework\TestCase;

class QueryValidatorTest extends TestCase
{
    /**
     * @return void
     */
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

        $b = function ($actual) use ($query, $error2, $error3): ListOfErrors {
            $this->assertSame($query, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $validator = new QueryValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b])
            ->validate($query);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    /**
     * @return void
     */
    public function testNoRules(): void
    {
        $query = $this->createMock(QueryInterface::class);
        $validator = new QueryValidator(new PipelineBuilderFactory());

        $this->assertEquals(new ListOfErrors(), $validator->validate($query));
    }
}
