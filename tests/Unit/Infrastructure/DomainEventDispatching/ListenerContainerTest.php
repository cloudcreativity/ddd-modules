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

namespace CloudCreativity\Modules\Tests\Unit\Infrastructure\DomainEventDispatching;

use CloudCreativity\Modules\Infrastructure\DomainEventDispatching\ListenerContainer;
use PHPUnit\Framework\TestCase;

class ListenerContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function testItCreatesListener(): void
    {
        $container = new ListenerContainer();
        $listener = new TestListener();

        $container->bind('bar', fn () => new TestListener());
        $container->bind('foo', fn () => $listener);

        $this->assertSame($listener, $container->get('foo'));
    }

    /**
     * @return void
     */
    public function testItDoesNotRecogniseListenerName(): void
    {
        $container = new ListenerContainer();
        $this->expectExceptionMessage('Unrecognised listener name: foo');
        $container->get('foo');
    }

    /**
     * @return void
     */
    public function testItHandlesBindingNotReturningAnObject(): void
    {
        $container = new ListenerContainer();
        $container->bind('foo', fn () => 'bar');
        $this->expectExceptionMessage('Listener binding for foo must return an object.');
        $container->get('foo');
    }
}
