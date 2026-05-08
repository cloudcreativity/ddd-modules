<?php

/*
 * Copyright 2026 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CloudCreativity\Modules\Infrastructure\Monolog;

use CloudCreativity\Modules\Contracts\Toolkit\Contextual;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final readonly class ContextualProcessor implements ProcessorInterface
{
    private ?ValueParser $parser;

    public function __construct(
        bool $recursive = false,
        bool $sorted = false,
        ?ValueParser $parser = null,
    ) {
        $this->parser = match (true) {
            $recursive => new RecursiveParser($sorted, $parser),
            default => $parser,
        };
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        foreach ($context as $key => $value) {
            if ($key === 'exception') {
                continue;
            }

            if ($value instanceof Contextual) {
                $value = $value->context();
            }

            $context[$key] = $this->parser ? $this->parser->parse($value) : $value;
        }

        return $record->with(context: $context);
    }
}
