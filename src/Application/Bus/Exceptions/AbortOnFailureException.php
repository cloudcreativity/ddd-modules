<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Exceptions;

use CloudCreativity\Modules\Toolkit\Result\FailedResultException;

final class AbortOnFailureException extends FailedResultException
{
}
