<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Bus\Validation;

use CloudCreativity\Modules\Bus\BusException;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Processor;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Error as IError;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;

final readonly class ValidationProcessor implements Processor
{
    public function __construct(private bool $stopOnFirstFailure = false)
    {
    }

    /**
     * @param callable(Message): (IError|IListOfErrors|null) ...$stages
     */
    public function process(mixed $payload, callable ...$stages): IListOfErrors
    {
        if (!$payload instanceof Message) {
            throw new BusException('Expecting a message to validate.');
        }

        $errors = new ListOfErrors();

        foreach ($stages as $stage) {
            $result = $stage($payload);

            $errors = match (true) {
                $result instanceof IListOfErrors => $errors->merge($result),
                $result instanceof IError => $errors->push($result),
                $result === null => $errors,
            };

            if ($this->stopOnFirstFailure && $errors->isNotEmpty()) {
                return $errors;
            }
        }

        return $errors;
    }
}
