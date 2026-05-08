<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Identifiers;

use JsonSerializable;
use Ramsey\Uuid\UuidInterface;

interface Uuid extends Identifier, JsonSerializable
{
    public function compareTo(self $other): int;

    public function toBase(): UuidInterface;
}
