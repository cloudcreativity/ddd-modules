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

namespace CloudCreativity\Modules\Tests\Unit\Toolkit;

use CloudCreativity\Modules\Toolkit\ContractException;
use CloudCreativity\Modules\Toolkit\Contracts;
use PHPUnit\Framework\TestCase;

class ContractsTest extends TestCase
{
    /**
     * @return void
     */
    public function testItDoesNotThrowWhenPreconditionIsTrue(): void
    {
        Contracts::assert(true, 'Not expected error.');
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testItThrowsWhenPreconditionIsFalse(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage($expected = 'The expected message.');

        Contracts::assert(false, $expected);
    }
}
