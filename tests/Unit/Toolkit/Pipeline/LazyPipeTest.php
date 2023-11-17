<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit\Pipeline;

use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\LazyPipe;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipeContainerInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Pipeline\PipelineInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LazyPipeTest extends TestCase
{
    /**
     * @var PipeContainerInterface&MockObject
     */
    private PipeContainerInterface $container;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(PipeContainerInterface::class);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('SomePipe')
            ->willReturn($pipe = $this->createMock(PipelineInterface::class));

        $pipe
            ->expects($this->once())
            ->method('__invoke')
            ->with('arg1', 'arg2')
            ->willReturn($expected = 'Hello World!');

        $lazyPipe = new LazyPipe($this->container, 'SomePipe');
        $actual = $lazyPipe('arg1', 'arg2');

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItRethrowsException(): void
    {
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('PipeThatWillError')
            ->willThrowException($expected = new \LogicException('Boom!'));

        $lazyPipe = new LazyPipe($this->container, 'PipeThatWillError');

        try {
            $lazyPipe('blah');
            $this->fail('No exception thrown.');
        } catch (\RuntimeException $ex) {
            $this->assertSame('Failed to get pipe "PipeThatWillError" from container.', $ex->getMessage());
            $this->assertSame(0, $ex->getCode());
            $this->assertSame($expected, $ex->getPrevious());
        }
    }
}
