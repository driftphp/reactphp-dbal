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
     * @deprecated
     * @var array options
     */
    private array $options;

    /**
     * @deprecated
     * @var int|null $connections
     */
    private ?int $connections = null;

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

        if (func_num_args() > 5) {
            trigger_error(
                '6th argument is deprecated, for options please use ' . ConnectionOptions::class  .
                ' or and extend of this class instead, when creating a connection',
                E_USER_DEPRECATED
            );
            $this->options = (array)func_get_arg(5);
        }
        if (func_num_args() > 6) {
            trigger_error(
                '7th argument is deprecated, please use ' . ConnectionPoolOptions::class  .
                ' to set the number of connections, when creating a ' . ConnectionPool::class,
                E_USER_DEPRECATED
            );
            $this->connections = (int)func_get_arg(6);
        }
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
     * @deprecated
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @deprecated
     * @return int|null
     */
    public function getConnections(): ?int
    {
        return $this->connections;
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
