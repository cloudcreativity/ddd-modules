<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Testing;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\ExceptionReporter;
use CloudCreativity\Modules\Testing\FakeExceptionReporter;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FakeExceptionReporterTest extends TestCase
{
    public function testItReportsExceptions(): void
    {
        $reporter = new FakeExceptionReporter();
        $reporter->report($ex1 = new RuntimeException('Whoops!'));
        $reporter->report($ex2 = new RuntimeException('Whoops again!'));

        $this->assertInstanceOf(ExceptionReporter::class, $reporter);
        $this->assertCount(2, $reporter);
        $this->assertSame([$ex1, $ex2], $reporter->reported);
    }

    public function testItHasSoleException(): void
    {
        $reporter = new FakeExceptionReporter();
        $reporter->report($ex = new RuntimeException('Whoops!'));

        $this->assertSame($ex, $reporter->sole());
    }

    public function testItFailsWhenThereIsNoSoleException(): void
    {
        $this->expectExceptionMessage('Expected one exception to be reported but there are 0 exceptions.');
        $this->expectException(LogicException::class);

        $reporter = new FakeExceptionReporter();
        $reporter->sole();
    }

    public function testItFailsWhenThereIsMoreThanOneSoleException(): void
    {
        $this->expectExceptionMessage('Expected one exception to be reported but there are 2 exceptions.');
        $this->expectException(LogicException::class);

        $reporter = new FakeExceptionReporter();
        $reporter->report(new RuntimeException('Whoops!'));
        $reporter->report(new RuntimeException('Whoops again!'));
        $reporter->sole();
    }
}
