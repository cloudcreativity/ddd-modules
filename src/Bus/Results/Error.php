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

namespace CloudCreativity\Modules\Bus\Results;

use InvalidArgumentException;

final class Error implements ErrorInterface
{
    /**
     * @var string|null
     */
    private ?string $key;

    /**
     * @var string
     */
    private string $message;

    /**
     * @var mixed|null
     */
    private mixed $code;

    /**
     * Error constructor.
     *
     * @param string|null $key
     * @param string $message
     * @param mixed|null $code
     */
    public function __construct(?string $key, string $message, mixed $code = null)
    {
        if (empty($message)) {
            throw new InvalidArgumentException('Expecting a non-empty error message.');
        }

        $this->key = $key ?: null;
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->message();
    }

    /**
     * @return string|null
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
    public function code(): mixed
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        return array_filter([
            'key' => $this->key,
            'message' => $this->message,
            'code' => $this->code,
        ], static fn ($value) => $value !== null);
    }
}
