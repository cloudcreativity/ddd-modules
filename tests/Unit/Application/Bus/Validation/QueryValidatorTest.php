<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\Bus\Validation;

use CloudCreativity\Modules\Application\Bus\Validation\QueryValidator;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
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
        $query = $this->createMock(Query::class);
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

        $rules = $this->createMock(PipeContainer::class);
        $rules
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'Rule2' => $b,
                'Rule3' => $c,
                default => $this->fail('Unexpected rule name: ' . $name),
            });

        $validator = new QueryValidator(rules: $rules);
        $actual = $validator
            ->using([$a, 'Rule2', 'Rule3'])
            ->validate($query);

        $this->assertInstanceOf(ListOfErrors::class, $actual);
        $this->assertSame([$error1, $error2, $error3], $actual->all());
    }

    /**
     * @return void
     */
    public function testNoRules(): void
    {
        $query = $this->createMock(Query::class);
        $validator = new QueryValidator();

        $this->assertEquals(new ListOfErrors(), $validator->validate($query));
    }
}
