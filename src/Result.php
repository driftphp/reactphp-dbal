<?php


namespace Drift\DBAL;

/**
 * Class Result
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
     * Fetch count
     */
    public function fetchCount()
    {
        return count($this->data);
    }

    /**
     * Fetch all rows
     */
    public function fetchAllRows()
    {
        return $this->data;
    }

    /**
     * Fetch first row
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