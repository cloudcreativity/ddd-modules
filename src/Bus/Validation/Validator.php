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

use CloudCreativity\Modules\Bus\PsrPipeContainer;
use CloudCreativity\Modules\Contracts\Bus\Validation\Validator as IValidator;
use CloudCreativity\Modules\Contracts\Messaging\Message;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\PipeContainer;
use CloudCreativity\Modules\Contracts\Toolkit\Pipeline\Pipeline;
use CloudCreativity\Modules\Contracts\Toolkit\Result\ListOfErrors as IListOfErrors;
use CloudCreativity\Modules\Toolkit\Pipeline\PipelineBuilder;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;
use Psr\Container\ContainerInterface;

final class Validator implements IValidator
{
    private readonly ?PipeContainer $rules;

    /**
     * @var iterable<callable|string>
     */
    private iterable $using = [];

    private bool $stopOnFirstFailure = false;

    public function __construct(ContainerInterface|PipeContainer|null $rules = null)
    {
        $this->rules = $rules instanceof ContainerInterface
            ? new PsrPipeContainer($rules)
            : $rules;
    }

    public function using(iterable $rules): static
    {
        $this->using = $rules;

        return $this;
    }

    public function stopOnFirstFailure(bool $stop = true): static
    {
        $this->stopOnFirstFailure = $stop;

        return $this;
    }

    public function validate(Message $message): IListOfErrors
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
            ->build(new ValidationProcessor($this->stopOnFirstFailure));
    }
}
