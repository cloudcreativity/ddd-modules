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
    /**
     * @var UnitEnum|string|null
     */
    private UnitEnum|string|null $key;

    /**
     * Error constructor.
     *
     * @param UnitEnum|string|null $key
     * @param string $message
     * @param UnitEnum|null $code
     */
    public function __construct(
        UnitEnum|string|null $key = null,
        private string $message = '',
        private ?UnitEnum $code = null,
    ) {
        Contracts::assert(!empty($message) || $code !== null, 'Error must have a message or a code.');
        $this->key = $key ?: null;
    }

    /**
     * @inheritDoc
     */
    public function key(): UnitEnum|string|null
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @inheritDoc
     */
    public function code(): ?UnitEnum
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function is(UnitEnum $code): bool
    {
        return $this->code === $code;
    }
}
