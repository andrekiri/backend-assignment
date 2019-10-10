<?php

namespace tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * The container implementation.
     *
     * @var \Slim\Container
     */
    protected $container;
    /**
     * The app implementation.
     *
     * @var \Slim\App
     */
    protected $app;

    public function setUp()
    {
        if ( ! $this->app) {
            $this->refreshApplication();
        }
        $this->container = $this->app->getContainer();

        // Delete log.
        file_put_contents(getcwd().'/logs/access.log', "");
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown()
    {
        if ($this->app) {

            $this->app = null;
        }

    }

    /**
     * Refresh the application instance.
     *
     * @return void
     */
    protected function refreshApplication()
    {
        $this->app = require __DIR__ . '/../initApp.php';
    }
}