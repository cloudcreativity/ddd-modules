<?php

/*
 * Copyright 2025 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Application\Bus;

use CloudCreativity\Modules\Contracts\Application\Bus\Validator as IValidator;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Pipeline;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Pipeline\AccumulationProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;

final class Validator implements IValidator
{
    /**
     * @var iterable<callable|string>
     */
    private iterable $using = [];

    public function __construct(private readonly ?PipeContainer $rules = null)
    {
    }

    /**
     * @param iterable<callable|string> $rules
     * @return $this
     */
    public function using(iterable $rules): static
    {
        $this->using = $rules;

        return $this;
    }

    public function validate(Command|Query $message): IListOfErrors
    {
        $errors = $this
            ->getPipeline()
            ->process($message) ?? new ListOfErrors();

        assert($errors instanceof IListOfErrors, 'Expecting validation pipeline to return errors.');

        return $errors;
    }

    private function getPipeline(): Pipeline
    {
        return PipelineBuilder::make($this->rules)
            ->through($this->using)
            ->build($this->processor());
    }

    private function processor(): AccumulationProcessor
    {
        return new AccumulationProcessor(
            static function (?IListOfErrors $carry, ?IListOfErrors $errors): IListOfErrors {
                $errors ??= new ListOfErrors();
                return $carry ? $carry->merge($errors) : $errors;
            },
        );
    }
}
