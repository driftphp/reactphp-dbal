<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author David Llop <d.lloople@icloud.com>
 */

declare(strict_types=1);

namespace Drift\DBAL\Tests;

use Drift\DBAL\Credentials;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionTest.
 */
class CredentialsTest extends TestCase
{
    /**
     * Test that credentials works as expected with all the fields filled.
     */
    public function testCredentialsCanBeConvertedToString()
    {
        $credentials = new Credentials('127.0.0.1', '3306', 'user', 'password', 'database');

        $this->assertEquals('user:password@127.0.0.1:3306/database', $credentials->toString());
    }

    /**
     * Test that credentials works as expected without login information.
     */
    public function testCredentialsCanBeConvertedToStringWithoutLogin()
    {
        $credentials = new Credentials('127.0.0.1', '3306', '', '', 'database');

        $this->assertEquals('127.0.0.1:3306/database', $credentials->toString());
    }

    /**
     * Test that credentials works as expected without password.
     */
    public function testCredentialsCanBeConvertedToStringWithoutPassword()
    {
        $credentials = new Credentials('127.0.0.1', '3306', 'user', '', 'database');

        $this->assertEquals('user@127.0.0.1:3306/database', $credentials->toString());
    }
}
