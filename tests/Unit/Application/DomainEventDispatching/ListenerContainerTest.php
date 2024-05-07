<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Application\DomainEventDispatching;

use CloudCreativity\Modules\Application\DomainEventDispatching\ListenerContainer;
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
