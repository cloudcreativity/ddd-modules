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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Infrastructure;
use CloudCreativity\BalancedEvent\Common\Infrastructure\InfrastructureException;
use PHPUnit\Framework\TestCase;

class InfrastructureTest extends TestCase
{
    /**
     * @return void
     */
    public function testItDoesNotThrowWhenPreconditionIsTrue(): void
    {
        Infrastructure::assert(true, 'Unexpected message.');

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testItThrowsWhenPreconditionIsFalse(): void
    {
        $this->expectException(InfrastructureException::class);
        $this->expectExceptionMessage($expected = 'Something was wrong.');

        Infrastructure::assert(false, $expected);
    }
}
