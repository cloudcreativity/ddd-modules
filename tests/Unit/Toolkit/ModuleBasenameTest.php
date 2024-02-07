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

namespace CloudCreativity\Modules\Tests\Unit\Toolkit;

use CloudCreativity\Modules\Toolkit\ModuleBasename;
use PHPUnit\Framework\TestCase;

class ModuleBasenameTest extends TestCase
{
    /**
     * @return array<array<string>>
     */
    public static function nameProvider(): array
    {
        return [
            [
                'CloudCreativity\Application\Modules\WaitList\BoundedContext\Application\Commands\ScheduleDelayedProcess\ScheduleDelayedProcessCommand',
                'WaitList',
                'ScheduleDelayedProcessCommand',
            ],
            [
                'CloudCreativity\Application\Modules\WaitList\BoundedContext\Application\Queries\GetScheduledProcessList\GetScheduledProcessListQuery',
                'WaitList',
                'GetScheduledProcessListQuery',
            ],
            [
                'CloudCreativity\Application\Modules\WaitList\BoundedContext\Domain\Events\TicketsWereReleased',
                'WaitList',
                'TicketsWereReleased',
            ],
            [
                'CloudCreativity\Application\Modules\WaitList\BoundedContext\Infrastructure\Queue\ProcessWaitListDto',
                'WaitList',
                'ProcessWaitListDto',
            ],
            [
                'Foo\Bar\Modules\Ordering\BoundedContext\Baz\Bat\FooBarCommand',
                'Ordering',
                'FooBarCommand',
            ],
            [
                'CloudCreativity\Application\Modules\BatchMailer\BoundedContext\Application\Commands\SendEmail\SendEmailCommand',
                'BatchMailer',
                'SendEmailCommand',
            ],
            [
                'CloudCreativity\Application\Modules\BatchMailer\BoundedContext\Infrastructure\Queue\SendEmailDto',
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
