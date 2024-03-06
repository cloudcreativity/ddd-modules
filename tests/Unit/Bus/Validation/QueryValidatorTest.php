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

use CloudCreativity\Modules\Bus\Validation\QueryValidator;
use CloudCreativity\Modules\Toolkit\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilderFactory;
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
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

        $b = function ($actual) use ($query): ?ListOfErrors {
            $this->assertSame($query, $actual);
            return null;
        };

        $c = function ($actual) use ($query, $error2, $error3): ListOfErrors {
            $this->assertSame($query, $actual);
            return new ListOfErrors($error2, $error3);
        };

        $validator = new QueryValidator(new PipelineBuilderFactory());
        $actual = $validator
            ->using([$a, $b, $c])
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
