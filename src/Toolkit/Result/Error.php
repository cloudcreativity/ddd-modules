<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Toolkit\Contracts;
use UnitEnum;

final readonly class Error implements IError
{
    private string|UnitEnum|null $key;

    public function __construct(
        private ?UnitEnum $code = null,
        private string $message = '',
        string|UnitEnum|null $key = null,
    ) {
        Contracts::assert(!empty($message) || $code !== null, 'Error must have a message or a code.');
        $this->key = $key ?: null;
    }

    public function key(): string|UnitEnum|null
    {
        return $this->key;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function code(): ?UnitEnum
    {
        return $this->code;
    }

    public function is(UnitEnum $code): bool
    {
        return $this->code === $code;
    }
}
