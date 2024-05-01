<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus\Validation;

use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors;

interface CommandValidatorInterface extends ValidatorInterface
{
    /**
     * Validate the provided command.
     *
     * @param Command $command
     * @return ListOfErrors
     */
    public function validate(Command $command): ListOfErrors;
}
