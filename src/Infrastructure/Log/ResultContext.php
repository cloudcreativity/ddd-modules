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

namespace CloudCreativity\Modules\Infrastructure\Log;

use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use CloudCreativity\Modules\Toolkit\Result\ErrorInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class ResultContext implements ContextProviderInterface
{
    /**
     * @param ResultInterface<mixed> $result
     * @return self
     */
    public static function from(ResultInterface $result): self
    {
        return new self($result);
    }

    /**
     * ResultContext constructor.
     *
     * @param ResultInterface<mixed> $result
     */
    public function __construct(private readonly ResultInterface $result)
    {
    }

    /**
     * @inheritDoc
     */
    public function context(): array
    {
        if ($this->result instanceof ContextProviderInterface) {
            return $this->result->context();
        }

        $value = $this->result->safe();
        $errors = $this->errors();
        $error = null;

        if (
            count($errors) === 1 &&
            count($errors[0]) === 1 &&
            (isset($errors[0]['message']) || isset($errors[0]['code']))
        ) {
            $error = $errors[0]['message'] ?? $errors[0]['code'];
            $errors = null;
        }

        return array_filter([
            'success' => $this->result->didSucceed(),
            'value' => match(true) {
                $value instanceof ContextProviderInterface => $value->context(),
                $value instanceof IdentifierInterface => $value->context(),
                default => null,
            },
            'error' => $error,
            'errors' => $errors ?: null,
            'meta' => $this->result->meta()->all() ?: null,
        ], static fn ($value) => $value !== null);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function errors(): array
    {
        return array_map(
            fn (ErrorInterface $error): array => $this->error($error),
            $this->result->errors()->all(),
        );
    }

    /**
     * @param ErrorInterface $error
     * @return array<string, mixed>
     */
    private function error(ErrorInterface $error): array
    {
        if ($error instanceof ContextProviderInterface) {
            return $error->context();
        }

        return array_filter([
            'code' => $error->code()?->value,
            'key' => $error->key(),
            'message' => $error->message(),
        ]);
    }
}
