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

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\UnitOfWork;
use CloudCreativity\Modules\Testing\FakeUnitOfWork;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FakeUnitOfWorkTest extends TestCase
{
    public function testItIsSuccessfulOnFirstAttempt(): void
    {
        $unitOfWork = new FakeUnitOfWork();
        $result = $unitOfWork->execute(fn () => 'result');

        $this->assertInstanceOf(UnitOfWork::class, $unitOfWork);
        $this->assertSame('result', $result);
        $this->assertSame(['attempt:1', 'commit:1'], $unitOfWork->sequence);
        $this->assertEmpty($unitOfWork->exceptions->reported);
    }

    public function testItIsSuccessfulBeforeMaxAttempts(): void
    {
        $ex1 = new RuntimeException('An error occurred.');
        $ex2 = new RuntimeException('Another error occurred.');
        $exceptions = [$ex1, $ex2];

        $unitOfWork = new FakeUnitOfWork();
        $result = $unitOfWork->execute(function () use (&$exceptions): string {
            $ex = array_shift($exceptions);
            if ($ex) {
                throw $ex;
            }
            return 'result';
        }, 4);

        $this->assertInstanceOf(UnitOfWork::class, $unitOfWork);
        $this->assertSame('result', $result);
        $this->assertSame([
            'attempt:1',
            'rollback:1',
            'attempt:2',
            'rollback:2',
            'attempt:3',
            'commit:3',
        ], $unitOfWork->sequence);
        $this->assertSame($unitOfWork->exceptions->reported, [$ex1, $ex2]);
    }

    public function testItIsSuccessfulOnMaxAttempts(): void
    {
        $ex1 = new RuntimeException('An error occurred.');
        $ex2 = new RuntimeException('Another error occurred.');
        $exceptions = [$ex1, $ex2];

        $unitOfWork = new FakeUnitOfWork();
        $result = $unitOfWork->execute(function () use (&$exceptions): string {
            $ex = array_shift($exceptions);
            if ($ex) {
                throw $ex;
            }
            return 'result';
        }, 3);

        $this->assertInstanceOf(UnitOfWork::class, $unitOfWork);
        $this->assertSame('result', $result);
        $this->assertSame([
            'attempt:1',
            'rollback:1',
            'attempt:2',
            'rollback:2',
            'attempt:3',
            'commit:3',
        ], $unitOfWork->sequence);
        $this->assertSame($unitOfWork->exceptions->reported, [$ex1, $ex2]);
    }

    public function testItIsNotSuccessful(): void
    {
        $ex1 = new RuntimeException('An error occurred.');
        $ex2 = new RuntimeException('Another error occurred.');
        $exceptions = [$ex1, $ex2];

        $unitOfWork = new FakeUnitOfWork();

        $this->expectExceptionObject($ex2);

        try {
            $unitOfWork->execute(function () use (&$exceptions): never {
                throw array_shift($exceptions);
            }, 2);
        } finally {
            $this->assertSame([
                'attempt:1',
                'rollback:1',
                'attempt:2',
                'rollback:2',
            ], $unitOfWork->sequence);
            $this->assertSame($unitOfWork->exceptions->reported, [$ex1, $ex2]);
        }
    }
}
