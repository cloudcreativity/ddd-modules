<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Loggable;

use CloudCreativity\Modules\Contracts\Toolkit\Messages\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

interface ContextFactory
{
    /**
     * Make log context for the provided object.
     *
     * @param Message|Result<mixed> $object
     * @return array<array-key, mixed>
     */
    public function make(Message|Result $object): array;
}
