<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Pipeline;

use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;
use PHPUnit\Framework\TestCase;

class PipeContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = fn () => 1;
        $b = fn () => 2;

        $container = new PipeContainer();
        $container->bind('PipeA', fn () => $a);
        $container->bind('PipeB', fn () => $b);

        $this->assertSame($a, $container->get('PipeA'));
        $this->assertSame($b, $container->get('PipeB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised pipe name: PipeC');

        $container->get('PipeC');
    }
}
