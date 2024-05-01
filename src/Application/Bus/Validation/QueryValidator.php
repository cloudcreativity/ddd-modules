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

use CloudCreativity\Modules\Contracts\Application\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;

final class QueryValidator extends Validator implements QueryValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(Query $query): IListOfErrors
    {
        $errors = $this->getPipeline()->process($query) ?? new ListOfErrors();

        assert($errors instanceof IListOfErrors, 'Expecting validation pipeline to return errors.');

        return $errors;
    }
}
