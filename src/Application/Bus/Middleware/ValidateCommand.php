<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Bus\Bail;
use CloudCreativity\Modules\Contracts\Application\Bus\CommandMiddleware;
use CloudCreativity\Modules\Contracts\Application\Bus\Validator;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result as IResult;
use CloudCreativity\Modules\Toolkit\Result\Result;

abstract class ValidateCommand implements CommandMiddleware
{
    /**
     * Get the rules for the validation.
     *
     * @return iterable<callable|string>
     */
    abstract protected function rules(): iterable;

    public function __construct(private readonly Validator $validator)
    {
    }

    public function __invoke(Command $command, Closure $next): IResult
    {
        $errors = $this->validator
            ->using($this->rules())
            ->stopOnFirstFailure($this instanceof Bail)
            ->validate($command);

        if ($errors->isNotEmpty()) {
            return Result::failed($errors);
        }

        return $next($command);
    }
}
