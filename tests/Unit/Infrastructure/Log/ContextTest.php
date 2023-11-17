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

namespace CloudCreativity\BalancedEvent\Tests\Unit\Common\Infrastructure\Log;

use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\Context;
use CloudCreativity\BalancedEvent\Common\Infrastructure\Log\ContextProviderInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Guid;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\IntegerId;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\StringId;
use CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers\Uuid;
use CloudCreativity\BalancedEvent\Modules\BatchMailer\Shared\Enums\BatchTypeEnum;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as RamseyUuid;

class ContextTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $provider = $this->createMock(ContextProviderInterface::class);
        $provider->expects($this->exactly(2))->method('context')->willReturn([
            'foo' => 'bar',
        ]);
        $stringable = $this->createMock(\Stringable::class);
        $stringable->method('__toString')->willReturn('some string!');

        $values = [
            'foobar' => $provider,
            'enum' => BatchTypeEnum::Attendee,
            'uuid' => $uuid = RamseyUuid::uuid4(),
            'identifiers' => [
                new IntegerId(1),
                new StringId('2'),
                new Uuid($uuid),
                new Guid('SomeType', new IntegerId(99)),
            ],
            'date_with_tz' => $date = new \DateTimeImmutable(
                '2021-01-01 12:13:14.123456',
                new \DateTimeZone('Australia/Melbourne'),
            ),
            'date_utc' => $date->setTimezone(new \DateTimeZone('UTC')),
            'stringable' => $stringable,
            'nested' => [
                $provider,
                BatchTypeEnum::Attendee,
                $uuid,
                $date,
                $stringable,
            ],
        ];

        $expected = [
            'foobar' => ['foo' => 'bar'],
            'enum' => 'attendee',
            'uuid' => $uuid->toString(),
            'identifiers' => [
                1,
                '2',
                $uuid->toString(),
                ['type' => 'SomeType', 'id' => 99],
            ],
            'date_with_tz' => '2021-01-01T12:13:14.123456+11:00',
            'date_utc' => '2021-01-01T01:13:14.123456Z',
            'stringable' => 'some string!',
            'nested' => [
                ['foo' => 'bar'],
                'attendee',
                $uuid->toString(),
                '2021-01-01T12:13:14.123456+11:00',
                'some string!',
            ],
        ];

        $this->assertSame($expected, Context::parse($values));
    }
}
