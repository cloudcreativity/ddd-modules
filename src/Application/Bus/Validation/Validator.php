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

use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Pipeline;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Pipeline\AccumulationProcessor;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;

abstract class Validator implements ValidatorInterface
{
    /**
     * @var iterable<string|callable>
     */
    private iterable $using = [];

    /**
     * AbstractValidator constructor
     *
     * @param PipeContainer|null $rules
     */
    public function __construct(private readonly ?PipeContainer $rules = null)
    {
    }

    /**
     * @param iterable<string|callable> $rules
     * @return $this
     */
    public function using(iterable $rules): self
    {
        $this->using = $rules;

        return $this;
    }

    /**
     * @return Pipeline
     */
    protected function getPipeline(): Pipeline
    {
        return PipelineBuilder::make($this->rules)
            ->through($this->using)
            ->build($this->processor());
    }

    /**
     * @return AccumulationProcessor
     */
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
