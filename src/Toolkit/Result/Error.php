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
