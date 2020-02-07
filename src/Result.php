<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\DBAL;

/**
 * Class Result.
 */
class Result
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * Result constructor.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Fetch count.
     */
    public function fetchCount()
    {
        return count($this->data);
    }

    /**
     * Fetch all rows.
     */
    public function fetchAllRows()
    {
        return $this->data;
    }

    /**
     * Fetch first row.
     *
     * @return mixed|null
     */
    public function fetchFirstRow()
    {
        return is_array($this->data)
            && count($this->data) >= 1
            ? reset($this->data)
            : null;
    }
}
