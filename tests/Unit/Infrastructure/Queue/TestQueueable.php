<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\QueueableInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\Guid;

class TestQueueable implements QueueableInterface
{
    /**
     * @var Guid|null
     */
    private ?Guid $guid = null;

    /**
     * @return Guid|null
     */
    public function getGuid(): ?Guid
    {
        return $this->guid;
    }

    /**
     * @param Guid|null $guid
     * @return $this
     */
    public function setGuid(?Guid $guid): self
    {
        $this->guid = $guid;

        return $this;
    }
}
