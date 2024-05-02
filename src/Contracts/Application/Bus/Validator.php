<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Contracts\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors;

interface Validator
{
    /**
     * Set the rules for the validation.
     *
     * @param iterable<string|callable> $rules
     * @return $this
     */
    public function using(iterable $rules): static;

    /**
     * Validate the provided message.
     *
     * @param Command|Query $message
     * @return ListOfErrors
     */
    public function validate(Command|Query $message): ListOfErrors;
}
