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

use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrorsInterface;

final class CommandValidator extends AbstractValidator implements CommandValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(CommandInterface $command): ListOfErrorsInterface
    {
        $errors = $this->getPipeline()->process($command) ?? new ListOfErrors();

        assert($errors instanceof ListOfErrorsInterface, 'Expecting validation pipeline to return errors.');

        return $errors;
    }
}
