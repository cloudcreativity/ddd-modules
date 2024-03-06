<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure;

use CloudCreativity\Modules\Infrastructure\Infrastructure;
use CloudCreativity\Modules\Infrastructure\InfrastructureException;
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
