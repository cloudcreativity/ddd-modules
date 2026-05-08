<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit;

use CloudCreativity\Modules\Contracts\Toolkit\ContractException as IContractException;
use LogicException;

final class ContractException extends LogicException implements IContractException
{
}
