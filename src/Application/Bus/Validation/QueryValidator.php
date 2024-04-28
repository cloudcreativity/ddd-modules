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

use CloudCreativity\Modules\Application\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrorsInterface;

final class QueryValidator extends AbstractValidator implements QueryValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(QueryInterface $query): ListOfErrorsInterface
    {
        $errors = $this->getPipeline()->process($query) ?? new ListOfErrors();

        assert($errors instanceof ListOfErrorsInterface, 'Expecting validation pipeline to return errors.');

        return $errors;
    }
}
