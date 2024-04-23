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
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerContainer;
use CloudCreativity\Modules\Tests\Unit\Application\Bus\TestCommand;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use PHPUnit\Framework\TestCase;

class EnqueuerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $command1 = new class () implements CommandInterface {};
        $command2 = new class () implements CommandInterface {};

        $a = new TestEnqueuer();
        $b = $this->createMock(TestEnqueuer::class);
        $default = $this->createMock(TestEnqueuer::class);

        $container = new EnqueuerContainer(fn () => $default);
        $container->bind($command1::class, fn () => $a);
        $container->bind($command2::class, fn () => $b);

        $this->assertEquals(new Enqueuer($a), $container->get($command1::class));
        $this->assertEquals(new Enqueuer($b), $container->get($command2::class));
        $this->assertEquals(new Enqueuer($default), $container->get(TestCommand::class));
    }
}
