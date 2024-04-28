<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Application\Bus\Validation\CommandValidatorInterface;
use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\Result;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

abstract class ValidateCommand implements CommandMiddlewareInterface
{
    /**
     * Get the rules for the validation.
     *
     * @return iterable<string|callable>
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
