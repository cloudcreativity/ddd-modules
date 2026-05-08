<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Result;

use Throwable;

interface FailedResultException extends Throwable
{
    /**
     * @return Result<mixed>
     */
    public function getResult(): Result;
}
