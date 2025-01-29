<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Loggable\ResultDecorator;
use RuntimeException;
use Throwable;

class FailedResultException extends RuntimeException implements ContextProvider
{
    /**
     * FailedResultException constructor.
     *
     * @param Result<mixed> $result
     */
    public function __construct(
        private readonly Result $result,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        assert($result->didFail(), 'Expecting a failed result.');
        parent::__construct($result->error() ?? '', $code, $previous);
    }

    /**
     * @return Result<mixed>
     */
    public function getResult(): Result
    {
        return $this->result;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return (new ResultDecorator($this->result))->context();
    }
}
