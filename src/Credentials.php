<?php


namespace Drift\DBAL;

/**
 * Class Credentials
 */
class Credentials
{
    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @var array
     */
    private $options;

    /**
     * Credentials constructor.
     *
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     * @param string $dbName
     * @param array  $options
     */
    public function __construct(
        string $host,
        string $port,
        string $user,
        string $password,
        string $dbName,
        array $options = []
    )
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->dbName = $dbName;
        $this->options = $options;
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
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * To string
     */
    public function toString() : string
    {
        $asString = sprintf(
            "%s:%s@%s:%d/%s",
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->dbName
        );

        return rawurldecode(
            str_replace(':@', '', $asString)
        );
    }
}