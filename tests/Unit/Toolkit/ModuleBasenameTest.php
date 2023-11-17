<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Toolkit;

use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;
use CloudCreativity\BalancedEvent\Common\Toolkit\ModuleBasename;
use CloudCreativity\BalancedEvent\Modules\WaitList\BoundedContext\Application\Commands\TriggerScheduledProcesses\TriggerScheduledProcessesCommand;
use CloudCreativity\BalancedEvent\Modules\WaitList\BoundedContext\Application\Queries\GetScheduledProcessList\GetScheduledProcessListQuery;
use CloudCreativity\BalancedEvent\Modules\WaitList\BoundedContext\Infrastructure\Queue\ProcessWaitListDto;
use PHPUnit\Framework\TestCase;

class ModuleBasenameTest extends TestCase
{
    /**
     * @return array
     */
    public static function nameProvider(): array
    {
        return [
            [
                'CloudCreativity\BalancedEvent\Modules\WaitList\BoundedContext\Application\Commands\ScheduleDelayedProcess\ScheduleDelayedProcessCommand',
                'WaitList',
                'ScheduleDelayedProcessCommand',
            ],
            [
                'CloudCreativity\BalancedEvent\Modules\WaitList\BoundedContext\Application\Queries\GetScheduledProcessList\GetScheduledProcessListQuery',
                'WaitList',
                'GetScheduledProcessListQuery',
            ],
            [
                'CloudCreativity\BalancedEvent\Modules\WaitList\BoundedContext\Domain\Events\TicketsWereReleased',
                'WaitList',
                'TicketsWereReleased',
            ],
            [
                'CloudCreativity\BalancedEvent\Modules\WaitList\BoundedContext\Infrastructure\Queue\ProcessWaitListDto',
                'WaitList',
                'ProcessWaitListDto',
            ],
            [
                'Foo\Bar\Modules\Ordering\BoundedContext\Baz\Bat\FooBarCommand',
                'Ordering',
                'FooBarCommand',
            ],
            [
                'CloudCreativity\BalancedEvent\Modules\BatchMailer\BoundedContext\Application\Commands\SendEmail\SendEmailCommand',
                'BatchMailer',
                'SendEmailCommand',
            ],
            [
                'CloudCreativity\BalancedEvent\Modules\BatchMailer\BoundedContext\Infrastructure\Queue\SendEmailDto',
                'BatchMailer',
                'SendEmailDto',
            ],
        ];
    }

    /**
     * @param string $value
     * @param string $context
     * @param string $message
     * @return void
     * @dataProvider nameProvider
     */
    public function testFrom(string $value, string $context, string $message): void
    {
        $name = ModuleBasename::from($value);

        $this->assertSame($context, $name->module);
        $this->assertSame($message, $name->name);
        $this->assertEquals($name, ModuleBasename::tryFrom($value));
        $this->assertSame("{$context}:{$message}", $name->toString());
        $this->assertSame("{$context}/{$message}", $name->toString('/'));
        $this->assertSame("{$context}:{$message}", (string) $name);
    }

    /**
     * @return array[]
     */
    public static function objectProvider(): array
    {
        return [
            'query' => [
                static fn() => new GetScheduledProcessListQuery(),
                'WaitList',
                'GetScheduledProcessListQuery',
            ],
            'command' => [
                static fn() => new TriggerScheduledProcessesCommand(),
                'WaitList',
                'TriggerScheduledProcessesCommand',
            ],
            'queueable' => [
                static fn() => new ProcessWaitListDto(
                    Guid::fromString('Event', '123'),
                ),
                'WaitList',
                'ProcessWaitListDto',
            ],
        ];
    }

    /**
     * @param \Closure $provider
     * @param string $context
     * @param string $message
     * @return void
     * @dataProvider objectProvider
     */
    public function testFromWithObject(\Closure $provider, string $context, string $message): void
    {
        $object = $provider();

        $name = ModuleBasename::from($object);

        $this->assertSame($context, $name->module);
        $this->assertSame($message, $name->name);
        $this->assertEquals($name, ModuleBasename::tryFrom($object));
    }

    /**
     * @param ModuleBasename $value
     * @return ModuleBasename
     */
    public function testToArray(): ModuleBasename
    {
        $value = ModuleBasename::from(GetScheduledProcessListQuery::class);

        $this->assertSame([
            'module' => $value->module,
            'name' => $value->name,
        ], $value->toArray());

        return $value;
    }

    /**
     * @param ModuleBasename $value
     * @return void
     * @depends testToArray
     */
    public function testJsonSerialize(ModuleBasename $value): void
    {
        $this->assertJsonStringEqualsJsonString(json_encode([
            'module' => $value->module,
            'name' => $value->name,
        ]), json_encode($value));
    }

    /**
     * @return void
     */
    public function testTryFromWithInvalid(): void
    {
        $name = ModuleBasename::tryFrom(ModuleBasename::class);

        $this->assertNull($name);
    }

    /**
     * @return void
     */
    public function testFromWithInvalid(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        ModuleBasename::from(ModuleBasename::class);
    }
}
