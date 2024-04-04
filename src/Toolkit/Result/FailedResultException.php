<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use CloudCreativity\Modules\Toolkit\Loggable\ContextProviderInterface;
use CloudCreativity\Modules\Toolkit\Loggable\ResultContext;
use RuntimeException;
use Throwable;

class FailedResultException extends RuntimeException implements ContextProviderInterface
{
    /**
     * FailedResultException constructor.
     *
     * @param ResultInterface<mixed> $result
     */
    public function __construct(
        private readonly ResultInterface $result,
        int $code = 0,
        Throwable $previous = null,
    ) {
        assert($result->didFail(), 'Expecting a failed result.');
        parent::__construct($result->error() ?? '', $code, $previous);
    }

    /**
     * @return ResultInterface<mixed>
     */
    public function getResult(): ResultInterface
    {
        return $this->result;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return ResultContext::from($this->result)->context();
    }
}
