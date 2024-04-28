<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Messages;

use CloudCreativity\Modules\Toolkit\Identifiers\Uuid;
use DateTimeImmutable;

interface IntegrationEventInterface extends MessageInterface
{
    /**
     * @return Uuid
     */
    public function uuid(): Uuid;

    /**
     * @return DateTimeImmutable
     */
    public function occurredAt(): DateTimeImmutable;
}
