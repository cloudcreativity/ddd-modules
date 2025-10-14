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
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use RuntimeException;
use Throwable;

use function CloudCreativity\Modules\Toolkit\enum_string;

class FailedResultException extends RuntimeException implements ContextProvider
{
    /**
     * @param Result<mixed> $result
     */
    public function __construct(
        private readonly Result $result,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        assert($result->didFail(), 'Expecting a failed result.');

        $message = match (true) {
            $result->error() !== null => $result->error(),
            $result->errors()->code() !== null => 'Failed with error code: ' . enum_string($result->errors()->code()),
            default => '',
        };

        parent::__construct(message: $message, code: $code, previous: $previous);
    }

    /**
     * @return Result<mixed>
     */
    public function getResult(): Result
    {
        return $this->result;
    }

    public function context(): array
    {
        return (new SimpleContextFactory())->make($this->result);
    }
}
