<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\ExceptionReporter;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\ExceptionReporter;
use CloudCreativity\Modules\Infrastructure\ExceptionReporter\PsrLogExceptionReporter;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PsrLogExceptionReporterTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface&MockObject $logger;

    /**
     * @var PsrLogExceptionReporter
     */
    private PsrLogExceptionReporter $reporter;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = new PsrLogExceptionReporter($this->logger);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->logger, $this->reporter);
    }

    public function testItReportsException(): void
    {
        $exception = new LogicException('Test exception');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $exception->getMessage(),
                ['exception' => $exception],
            );

        $this->reporter->report($exception);

        $this->assertInstanceOf(ExceptionReporter::class, $this->reporter);
    }

    public function testItReportsDefaultMessageIfExceptionHasEmptyMessage(): void
    {
        $exception = new LogicException();

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected error: LogicException',
                ['exception' => $exception],
            );

        $this->reporter->report($exception);

        $this->assertInstanceOf(ExceptionReporter::class, $this->reporter);
    }
}
