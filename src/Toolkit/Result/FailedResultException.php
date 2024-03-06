<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use RuntimeException;
use Throwable;

class FailedResultException extends RuntimeException
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
}
