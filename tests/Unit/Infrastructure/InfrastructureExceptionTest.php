<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure;

use CloudCreativity\Modules\Infrastructure\InfrastructureException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InfrastructureExceptionTest extends TestCase
{
    public function test(): void
    {
        $ex = new InfrastructureException();
        $this->assertInstanceOf(RuntimeException::class, $ex);
    }
}
