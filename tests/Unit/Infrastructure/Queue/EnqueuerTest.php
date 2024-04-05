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

use CloudCreativity\Modules\Infrastructure\Queue\Enqueuer;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use PHPUnit\Framework\TestCase;

class EnqueuerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $command = $this->createMock(CommandInterface::class);
        $innerEnqueuer = $this->createMock(TestEnqueuer::class);

        $innerEnqueuer
            ->expects($this->once())
            ->method('push')
            ->with($this->identicalTo($command));

        $enqueuer = new Enqueuer($innerEnqueuer);
        $enqueuer($command);
    }
}
