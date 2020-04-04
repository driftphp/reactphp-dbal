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
    private $rows;

    /**
     * @var int|null
     */
    private $lastInsertedId;

    /**
     * @var int|null
     */
    private $affectedRows;

    /**
     * Result constructor.
     *
     * @param mixed $rows
     * @param int|null $lastInsertedId
     * @param int|null $affectedRows
     */
    public function __construct(
        $rows,
        ?int $lastInsertedId,
        ?int $affectedRows
    )
    {
        $this->rows = $rows;
        $this->lastInsertedId = $lastInsertedId;
        $this->affectedRows = $affectedRows;
    }

    /**
     * Fetch count.
     *
     * @return int
     */
    public function fetchCount() : int
    {
        return is_array($this->rows)
            ? count($this->rows)
            : 0;
    }

    /**
     * Fetch all rows.
     *
     * @return mixed
     */
    public function fetchAllRows()
    {
        return $this->rows;
    }

    /**
     * Fetch first row.
     *
     * @return mixed|null
     */
    public function fetchFirstRow()
    {
        return is_array($this->rows)
            && count($this->rows) >= 1
            ? reset($this->rows)
            : null;
    }

    /**
     * @return int|null
     */
    public function getLastInsertedId(): ?int
    {
        return $this->lastInsertedId;
    }

    /**
     * @return int|null
     */
    public function getAffectedRows(): ?int
    {
        return $this->affectedRows;
    }
}
