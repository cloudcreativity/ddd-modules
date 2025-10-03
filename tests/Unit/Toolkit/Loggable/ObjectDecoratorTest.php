<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Tests\Unit\Toolkit\Loggable;

use CloudCreativity\Modules\Contracts\Toolkit\Loggable\ContextProvider;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Message;
use CloudCreativity\Modules\Tests\TestBackedEnum;
use CloudCreativity\Modules\Tests\TestBackedIntEnum;
use CloudCreativity\Modules\Tests\TestUnitEnum;
use CloudCreativity\Modules\Toolkit\Loggable\ObjectDecorator;
use CloudCreativity\Modules\Toolkit\Loggable\Sensitive;
use CloudCreativity\Modules\Toolkit\Loggable\SimpleContextFactory;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use UnitEnum;

class ObjectDecoratorTest extends TestCase
{
    private SimpleContextFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new SimpleContextFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->factory);
    }

    public function testItUsesObjectProperties(): void
    {
        $uuid = Uuid::uuid4();
        $date1 = new \DateTimeImmutable();
        $date2 = new \DateTime('2025-01-01 12:34:56', new \DateTimeZone('Australia/Sydney'));

        $source = new class ($uuid, $date1, $date2, $date2->getTimezone()) implements Message {
            public string $foo = 'bar';
            public string $baz = 'bat';
            public ?string $blah = null;
            public UnitEnum $enum1 = TestBackedEnum::Foo;
            public UnitEnum $enum2 = TestBackedIntEnum::FooBar;
            public UnitEnum $enum3 = TestUnitEnum::Baz;
            protected string $foobar = 'foobar';

            public function __construct(
                public UuidInterface $uuid,
                public DateTimeInterface $date1,
                public DateTimeInterface $date2,
                public \DateTimeZone $timeZone,
            ) {
            }
        };

        $expected = [
            'foo' => 'bar',
            'baz' => 'bat',
            'blah' => null,
            'enum1' => TestBackedEnum::Foo->value,
            'enum2' => TestBackedIntEnum::FooBar->name,
            'enum3' => TestUnitEnum::Baz->name,
            'uuid' => $uuid->toString(),
            'date1' => $date1->format('Y-m-d\TH:i:s.uP'),
            'date2' => $date2->format('Y-m-d\TH:i:s.uP'),
            'timeZone' => 'Australia/Sydney',
        ];

        $decorator = new ObjectDecorator($source);

        $this->assertInstanceOf(ContextProvider::class, $decorator);
        $this->assertSame($expected, iterator_to_array($decorator));
        $this->assertSame($expected, $decorator->all());
        $this->assertSame($expected, $decorator->context());
        $this->assertSame($expected, $this->factory->make($source));
        $this->assertSame(array_keys($expected), $decorator->keys());
    }

    public function testItCanCustomiseDateFormat(): void
    {
        $date = new DateTimeImmutable('2025-01-01 12:34:56', new \DateTimeZone('Australia/Sydney'));

        $source = new class ($date) implements Message {
            public function __construct(public DateTimeInterface $date)
            {
            }
        };

        $expected = [
            'date' => $date->format('Y-m-d H:i:s'),
        ];

        $decorator = new ObjectDecorator($source, dateFormat: 'Y-m-d H:i:s');

        $this->assertSame($expected, $decorator->context());
    }

    public function testItCanTurnOffDateFormatting(): void
    {
        $date = new DateTimeImmutable('2025-01-01 12:34:56', new \DateTimeZone('Australia/Sydney'));

        $source = new class ($date) implements Message {
            public function __construct(public DateTimeInterface $date)
            {
            }
        };

        $expected = [
            'date' => $date,
        ];

        $decorator = new ObjectDecorator($source, dateFormat: null);

        $this->assertSame($expected, $decorator->context());
    }

    public function testItExcludesSensitiveProperties(): void
    {
        $source = new class ('Hello', 'World') implements Message {
            public string $foo = 'bar';
            #[Sensitive]
            public string $baz = 'bat';
            public string $foobar = 'foobar';

            public function __construct(
                #[Sensitive] public string $blah1,
                public string $blah2,
            ) {
            }
        };

        $expected = [
            'foo' => 'bar',
            'foobar' => 'foobar',
            'blah2' => 'World',
        ];

        $decorator = new ObjectDecorator($source);

        $this->assertSame(array_keys($expected), $decorator->keys());
        $this->assertSame($expected, $decorator->all());
        $this->assertSame($expected, $decorator->context());
        $this->assertSame($expected, $this->factory->make($source));
    }
}
