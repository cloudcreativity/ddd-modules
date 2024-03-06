<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Toolkit\Result;

use BackedEnum;
use CloudCreativity\Modules\Toolkit\Contracts;

final class Error implements ErrorInterface
{
    /**
     * @var string|null
     */
    private readonly ?string $key;

    /**
     * Error constructor.
     *
     * @param string|null $key
     * @param string $message
     * @param BackedEnum|null $code
     */
    public function __construct(
        string|null $key = null,
        private readonly string $message = '',
        private readonly ?BackedEnum $code = null,
    ) {
        Contracts::assert(!empty($message) || $code !== null, 'Error must have a message or a code.');
        $this->key = $key ?: null;
    }

    /**
     * @inheritDoc
     */
    public function key(): ?string
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
    public function code(): ?BackedEnum
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function is(BackedEnum $code): bool
    {
        return $this->code === $code;
    }
}
