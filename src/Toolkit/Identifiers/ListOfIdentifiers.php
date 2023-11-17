<?php
/*
 * Copyright (C) Cloud Creativity Ltd - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Proprietary and confidential.
 *
 * Written by Cloud Creativity Ltd <info@cloudcreativity.co.uk>, 2023
 */

declare(strict_types=1);

namespace CloudCreativity\BalancedEvent\Common\Toolkit\Identifiers;

use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\ListInterface;
use CloudCreativity\BalancedEvent\Common\Toolkit\Iterables\ListTrait;

/**
 * @implements ListInterface<int, IdentifierInterface>
 */
class ListOfIdentifiers implements ListInterface
{
    use ListTrait;

    /**
     * @var array<int, IdentifierInterface>
     */
    private readonly array $stack;

    /**
     * ListOfIdentifiers constructor.
     *
     * @param IdentifierInterface ...$guids
     */
    public function __construct(IdentifierInterface ...$guids)
    {
        $this->stack = $guids;
    }

    /**
     * @return LazyListOfGuids
     */
    public function guids(): LazyListOfGuids
    {
        return new LazyListOfGuids(function () {
            yield from $this->stack;
        });
    }

    /**
     * @return LazyListOfIntegerIds
     */
    public function integerIds(): LazyListOfIntegerIds
    {
        return new LazyListOfIntegerIds(function () {
            yield from $this->stack;
        });
    }

    /**
     * @return LazyListOfStringIds
     */
    public function stringIds(): LazyListOfStringIds
    {
        return new LazyListOfStringIds(function () {
            yield from $this->stack;
        });
    }

    /**
     * @return LazyListOfUuids
     */
    public function uuids(): LazyListOfUuids
    {
        return new LazyListOfUuids(function () {
            yield from $this->stack;
        });
    }
}
