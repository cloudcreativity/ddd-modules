<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Contracts\Toolkit\Result\FailedResultException;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use RuntimeException;

/**
 * @internal
 */
final class AbortOnFailureException extends RuntimeException implements FailedResultException
{
    /**
     * @param Result<mixed> $result
     */
    public function __construct(private readonly Result $result)
    {
        parent::__construct();
    }

    public function getResult(): Result
    {
        return $this->result;
    }
}
