<?php

/*
 * This file is part of the FOSHttpCache package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCache\Tests\Functional;

/**
 * A PHPUnit test listener that starts and stops the PHP built-in web server
 *
 * This listener is configured with a couple of constants from the phpunit.xml
 * file. To define constants in the phpunit file, use this syntax:
 * <php>
 *     <const name="WEB_SERVER_HOSTNAME" value="localhost" />
 * </php>
 *
 * WEB_SERVER_HOSTNAME host name of the web server (required)
 * WEB_SERVER_PORT     port to listen on (required)
 * WEB_SERVER_DOCROOT  path to the document root for the server (required)
 */
class WebServerListener implements \PHPUnit_Framework_TestListener
{
    /**
     * PHP web server PID
     *
     * @var int
     */
    protected $pid;

    /**
     * Make sure the PHP built-in web server is running for tests with group
     * 'webserver'
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        // Only run on PHP >= 5.4 as PHP below that and HHVM don't have a
        // built-in web server
        if (defined('HHVM_VERSION') || version_compare(PHP_VERSION, '5.4.0', '<')) {
            return;
        }

        if (!in_array('webserver', $suite->getGroups()) || null !== $this->pid) {
            return;
        }

        $this->pid = $pid = $this->startPhpWebServer();

        register_shutdown_function(function () use ($pid) {
            exec('kill ' . $pid);
        });
    }

    /**
     *  We don't need these
     */
    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {}
    public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}
    public function addFailure(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time) {}
    public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}
    public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}
    public function startTest(\PHPUnit_Framework_Test $test) {}
    public function endTest(\PHPUnit_Framework_Test $test, $time) {}
    public function addRiskyTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}

    /**
     * Get web server hostname
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function getHostName()
    {
        if (!defined('WEB_SERVER_HOSTNAME')) {
            throw new \Exception('Set WEB_SERVER_HOSTNAME in your phpunit.xml');
        }

        return WEB_SERVER_HOSTNAME;
    }

    /**
     * Get web server port
     *
     * @throws \Exception
     *
     * @return int
     */
    protected function getPort()
    {
        if (!defined('WEB_SERVER_PORT')) {
            throw new \Exception('Set WEB_SERVER_PORT in your phpunit.xml');
        }

        return WEB_SERVER_PORT;
    }

    /**
     * Get web server port
     *
     * @throws \Exception
     *
     * @return int
     */
    protected function getDocRoot()
    {
        if (!defined('WEB_SERVER_DOCROOT')) {
            throw new \Exception('Set WEB_SERVER_DOCROOT in your phpunit.xml');
        }

        return WEB_SERVER_DOCROOT;
    }

    /**
     * Start PHP built-in web server
     *
     * @return int PID
     */
    protected function startPhpWebServer()
    {
        $command = sprintf(
            'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
            $this->getHostName(),
            $this->getPort(),
            $this->getDocRoot()
        );
        exec($command, $output);

        return $output[0];
    }
}
