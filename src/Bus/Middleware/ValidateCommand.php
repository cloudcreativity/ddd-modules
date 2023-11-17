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

namespace CloudCreativity\Modules\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Bus\CommandInterface;
use CloudCreativity\Modules\Bus\Results\Result;
use CloudCreativity\Modules\Bus\Results\ResultInterface;
use CloudCreativity\Modules\Bus\Validation\CommandValidatorInterface;

abstract class ValidateCommand implements CommandMiddlewareInterface
{
    /**
     * Get the rules for the validation.
     *
     * @return iterable
     */
    abstract protected function rules(): iterable;

    /**
     * ValidateCommand constructor.
     *
     * @param CommandValidatorInterface $validator
     */
    public function __construct(private readonly CommandValidatorInterface $validator)
    {
    }

    /**
     * @inheritDoc
     */
    public function __invoke(CommandInterface $command, Closure $next): ResultInterface
    {
        $errors = $this->validator
            ->using($this->rules())
            ->validate($command);

        if ($errors->isNotEmpty()) {
            return Result::failed($errors);
        }

        return $next($command);
    }
}
