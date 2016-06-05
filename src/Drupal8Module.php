<?php

namespace Codeception\Module;

use Codeception\Configuration;
use Codeception\Lib\Framework;
use Codeception\Lib\ModuleContainer;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpKernel\Client;

class Drupal8Module extends Framework
{
    /**
     * @var DrupalKernel
     */
    protected $kernel;

    /**
     * @var Client
     */
    protected $client;

    /**
     * Drupal8Module constructor.
     */
    public function __construct(ModuleContainer $container, $config = null)
    {
        $this->config = array_merge(
          [
            'core_path' => __DIR__ . '/web/core',
            'create_users' => true
          ],
          (array)$config
        );
        $autoloader = Configuration::projectDir() . '/autoload.php';
        $this->kernel = new DrupalKernel('test', $autoloader);
        parent::__construct($container);
    }

    public function _before(TestCase $test)
    {
        $this->client = new Client($this->kernel);
        $this->client->followRedirects(true);
    }

    public function _after(TestCase $test)
    {
        $this->kernel->shutdown();
    }
}