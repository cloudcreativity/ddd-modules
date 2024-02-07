<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\Modules\Tests\Unit\Bus;

use CloudCreativity\Modules\Bus\CommandHandler;
use CloudCreativity\Modules\Bus\CommandHandlerContainer;
use PHPUnit\Framework\TestCase;

class CommandHandlerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $a = new TestCommandHandler();
        $b = $this->createMock(TestCommandHandler::class);

        $container = new CommandHandlerContainer();
        $container->bind('CommandClassA', fn () => $a);
        $container->bind('CommandClassB', fn () => $b);

        $this->assertEquals(new CommandHandler($a), $container->get('CommandClassA'));
        $this->assertEquals(new CommandHandler($b), $container->get('CommandClassB'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No command handler bound for command class: CommandClassC');

        $container->get('CommandClassC');
    }
}
