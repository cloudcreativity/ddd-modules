<?php

/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit;

use CloudCreativity\Modules\Toolkit\ModuleBasename;
use PHPUnit\Framework\TestCase;

class ModuleBasenameTest extends TestCase
{
    /**
     * @return array<array<string>>
     */
    public static function moduleProvider(): array
    {
        return [
            [
                'App\Modules\WaitList\BoundedContext\Application\Commands\ScheduleDelayedProcess\ScheduleDelayedProcessCommand',
                'WaitList',
                'ScheduleDelayedProcessCommand',
            ],
            [
                'App\Modules\WaitList\Application\Commands\ScheduleDelayedProcess\ScheduleDelayedProcessCommand',
                'WaitList',
                'ScheduleDelayedProcessCommand',
            ],
            [
                'App\Modules\WaitList\BoundedContext\Application\Queries\GetScheduledProcessList\GetScheduledProcessListQuery',
                'WaitList',
                'GetScheduledProcessListQuery',
            ],
            [
                'App\Modules\WaitList\Application\Queries\GetScheduledProcessList\GetScheduledProcessListQuery',
                'WaitList',
                'GetScheduledProcessListQuery',
            ],
            [
                'Vendor\Modules\Podcasts\Shared\IntegrationEvents\PodcastPublished',
                'Podcasts',
                'PodcastPublished',
            ],
            [
                'Vendor\Modules\Podcasts\Shared\IntegrationEvents\PodcastPublished',
                'Podcasts',
                'PodcastPublished',
            ],
            [
                'App\Modules\WaitList\BoundedContext\Domain\Events\TicketsWereReleased',
                'WaitList',
                'TicketsWereReleased',
            ],
            [
                'App\Modules\WaitList\BoundedContext\Infrastructure\Queue\ProcessWaitList\ProcessWaitListJob',
                'WaitList',
                'ProcessWaitListJob',
            ],
            [
                'App\Modules\WaitList\Infrastructure\Queue\ProcessWaitList\ProcessWaitListJob',
                'WaitList',
                'ProcessWaitListJob',
            ],
            [
                'Foo\Bar\Modules\Ordering\BoundedContext\Baz\Bat\FooBarCommand',
                'Ordering',
                'FooBarCommand',
            ],
            [
                'App\Modules\BatchMailer\BoundedContext\Application\Commands\SendEmail\SendEmailCommand',
                'BatchMailer',
                'SendEmailCommand',
            ],
            [
                'App\Modules\BatchMailer\Application\Commands\SendEmail\SendEmailCommand',
                'BatchMailer',
                'SendEmailCommand',
            ],
            [
                'App\Modules\BatchMailer\Infrastructure\Queue\SendEmail\SendEmailJob',
                'BatchMailer',
                'SendEmailJob',
            ],
        ];
    }

    /**
     * Test module names when modules are not in use.
     *
     * This scenario happens when an application only has one bounded context,
     * i.e. one module. For example, a microservice that represents that bounded context.
     *
     * @return array<array<string>>
     */
    public static function withoutModuleProvider(): array
    {
        return [
            [
                'App\BoundedContext\Application\Queries\GetTradeIn\GetTradeInQuery',
                'GetTradeInQuery',
            ],
            [
                'App\BoundedContext\Application\Commands\SubmitTradeIn\SubmitTradeInCommand',
                'SubmitTradeInCommand',
            ],
            [
                'App\BoundedContext\Domain\Events\TradeInSubmitted',
                'TradeInSubmitted',
            ],
            [
                'App\BoundedContext\Shared\IntegrationEvents\TradeInSubmitted',
                'TradeInSubmitted',
            ],
        ];
    }

    /**
     * @param string $value
     * @param string $context
     * @param string $message
     * @return void
     * @dataProvider moduleProvider
     */
    public function testFromModule(string $value, string $context, string $message): void
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
     * @param string $value
     * @param string $message
     * @return void
     * @dataProvider withoutModuleProvider
     */
    public function testFromWithoutModule(string $value, string $message): void
    {
        $name = ModuleBasename::from($value);

        $this->assertNull($name->module);
        $this->assertSame($message, $name->name);
        $this->assertEquals($name, ModuleBasename::tryFrom($value));
        $this->assertSame($message, $name->toString());
        $this->assertSame($message, $name->toString('/'));
        $this->assertSame($message, (string) $name);
    }

    /**
     * @return ModuleBasename
     */
    public function testToArray(): ModuleBasename
    {
        $value = ModuleBasename::from(
            'CloudCreativity\Application\Modules\WaitList\BoundedContext\Application\Commands\ScheduleDelayedProcess\ScheduleDelayedProcessCommand',
        );

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
        $expected = json_encode([
            'module' => $value->module,
            'name' => $value->name,
        ], JSON_THROW_ON_ERROR);

        $this->assertJsonStringEqualsJsonString($expected, json_encode($value, JSON_THROW_ON_ERROR));
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
