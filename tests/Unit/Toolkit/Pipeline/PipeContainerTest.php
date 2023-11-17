<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Pipeline;

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipeContainer;
use PHPUnit\Framework\TestCase;

class PipeContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = fn() => 1;
        $b = fn() => 2;

        $container = new PipeContainer();
        $container->bind('PipeA', fn() => $a);
        $container->bind('PipeB', fn() => $b);

        $this->assertSame($a, $container->get('PipeA'));
        $this->assertSame($b, $container->get('PipeB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised pipe name: PipeC');

        $container->get('PipeC');
    }
}
