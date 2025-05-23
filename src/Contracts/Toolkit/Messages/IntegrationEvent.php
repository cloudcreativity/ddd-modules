<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Toolkit\Messages;

use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use DateTimeImmutable;

interface IntegrationEvent extends Message
{
    /**
     * @return Uuid
     */
    public function getUuid(): Uuid;

    /**
     * @return DateTimeImmutable
     */
    public function getOccurredAt(): DateTimeImmutable;
}
