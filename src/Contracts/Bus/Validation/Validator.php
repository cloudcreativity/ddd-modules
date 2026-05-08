<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Bus\Validation;

use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors;

interface Validator
{
    /**
     * Set the rules for the validation.
     *
     * @param iterable<callable|string> $rules
     * @return $this
     */
    public function using(iterable $rules): static;

    /**
     * Stop validating as soon as the first rule fails.
     *
     * @return $this
     */
    public function stopOnFirstFailure(bool $stop = true): static;

    /**
     * Validate the provided message.
     */
    public function validate(Message $message): ListOfErrors;
}
