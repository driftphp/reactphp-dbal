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
 * Class Credentials.
 */
class Credentials
{
    private string $host;
    private string $port;
    private string $user;
    private string $password;
    private string $dbName;

    /**
     * Credentials constructor.
     *
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     * @param string $dbName
     */
    public function __construct(
        string $host,
        string $port,
        string $user,
        string $password,
        string $dbName
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->dbName = $dbName;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getDbName(): string
    {
        return $this->dbName;
    }

    /**
     * To string.
     */
    public function toString(): string
    {
        $asString = sprintf(
            '%s:%s@%s:%d/%s',
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->dbName
        );

        if (0 === strpos($asString, ':@')) {
            return rawurldecode(
                substr($asString, 2)
            );
        }

        return rawurldecode(
            str_replace(':@', '@', $asString)
        );
    }
}
