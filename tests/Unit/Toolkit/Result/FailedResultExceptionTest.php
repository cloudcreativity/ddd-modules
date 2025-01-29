<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Result;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Toolkit\Loggable\ResultDecorator;
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;
use CloudCreativity\Modules\Toolkit\Result\Result;
use PHPUnit\Framework\TestCase;

class FailedResultExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $result = Result::failed('Something went wrong.')
            ->withMeta(['foo' => 'bar']);

        $exception = new FailedResultException($result);

        $this->assertSame($result, $exception->getResult());
        $this->assertSame('Something went wrong.', $exception->getMessage());
        $this->assertInstanceOf(ContextProvider::class, $exception);
        $this->assertSame((new ResultDecorator($result))->context(), $exception->context());
    }

    /**
     * @return void
     */
    public function testItHasCodeAndPreviousException(): void
    {
        $result = Result::failed('Something went wrong.');
        $previous = new \LogicException('Boom!');

        $exception = new FailedResultException($result, 99, $previous);

        $this->assertSame($result, $exception->getResult());
        $this->assertSame('Something went wrong.', $exception->getMessage());
        $this->assertSame(99, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
