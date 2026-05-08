<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Identifiers;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Uuid as IUuid;
use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\UuidFactory as IUuidFactory;
use CloudCreativity\Modules\Toolkit\ContractException;
use Ramsey\Uuid\Uuid as BaseUuid;
use Ramsey\Uuid\UuidInterface as IBaseUuid;

final class Uuid implements IUuid
{
    use IsUuid;

    private static ?IUuidFactory $factory = null;

    public static function setFactory(?IUuidFactory $factory): void
    {
        self::$factory = $factory;
    }

    public static function getFactory(): IUuidFactory
    {
        if (self::$factory) {
            return self::$factory;
        }

        return self::$factory = new UuidFactory();
    }

    public static function from(IBaseUuid|Identifier|string|null $value): IUuid
    {
        $factory = self::getFactory();

        return match(true) {
            $value instanceof Identifier, $value instanceof IBaseUuid => $factory->from($value),
            is_string($value) => $factory->fromString($value),
            $value === null => throw new ContractException('Unexpected identifier type, received: null'),
        };
    }

    public static function tryFrom(IBaseUuid|Identifier|string|null $value): ?IUuid
    {
        return match(true) {
            $value instanceof IUuid => $value,
            $value instanceof IBaseUuid => self::getFactory()->from($value),
            is_string($value) && BaseUuid::isValid($value) => self::getFactory()->fromString($value),
            default => null,
        };
    }

    /**
     * Generate a random UUID, useful in tests.
     */
    public static function random(): UuidV4
    {
        return self::getFactory()->uuid4();
    }

    /**
     * Create a nil UUID.
     */
    public static function nil(): IUuid
    {
        return self::from(BaseUuid::NIL);
    }

    public function __construct(public readonly IBaseUuid $value)
    {
    }
}
