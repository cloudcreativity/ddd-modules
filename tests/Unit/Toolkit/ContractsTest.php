<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
        /** @phpstan-ignore-next-line */
        Contracts::assert(true, 'Not expected error.');

        /** @phpstan-ignore-next-line */
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testItDoesNotThrowWhenPreconditionIsTrueWithLazyMessage(): void
    {
        /** @phpstan-ignore-next-line */
        Contracts::assert(true, fn () => $this->fail('Not expected error.'));

        /** @phpstan-ignore-next-line */
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testItThrowsWhenPreconditionIsFalse(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage($expected = 'The expected message.');

        /** @phpstan-ignore-next-line */
        Contracts::assert(false, $expected);
    }

    /**
     * @return void
     */
    public function testItThrowsWhenPreconditionIsFalseWithLazyMessage(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage($expected = 'The expected message.');

        /** @phpstan-ignore-next-line */
        Contracts::assert(false, fn () => $expected);
    }
}
