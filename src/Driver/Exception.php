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

namespace Drift\DBAL\Driver;

use Throwable;

/**
 * Class Exception.
 */
class Exception extends \Exception implements \Doctrine\DBAL\Driver\Exception
{
    /**
     * The SQLSTATE of the driver.
     *
     * @var string|null
     */
    private $sqlState;

    /**
     * @param string         $message  The driver error message.
     * @param string|null    $sqlState The SQLSTATE the driver is in at the time the error occurred, if any.
     * @param int            $code     The driver specific error code if any.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct($message, $sqlState = null, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->sqlState = $sqlState;
    }

    public function getSQLState()
    {
        return $this->sqlState;
    }
}
