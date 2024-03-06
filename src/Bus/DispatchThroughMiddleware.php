<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus;

use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware as BaseDispatchThroughMiddleware;

/**
 * @deprecated 1.0.0 use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware
 */
interface DispatchThroughMiddleware extends BaseDispatchThroughMiddleware
{
}
