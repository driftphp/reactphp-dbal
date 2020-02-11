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

use Doctrine\DBAL\Driver\DriverException;
use Exception;

/**
 * Class PlainDriverException.
 */
final class PlainDriverException extends Exception implements DriverException
{
    /**
     * @var string
     */
    private $sqlState;

    /**
     * Create by sqlstate.
     *
     * @param string $message
     * @param string $sqlState
     *
     * @return PlainDriverException
     */
    public static function createFromMessageEndErrorCode(
        string $message,
        string $sqlState
    ) {
        $exception = new PlainDriverException($message);
        $exception->sqlState = $sqlState;

        return $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorCode()
    {
        return $this->sqlState;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLState()
    {
        return $this->sqlState;
    }
}
