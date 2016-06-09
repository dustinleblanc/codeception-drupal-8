<?php

namespace Codeception\Module;

use Codeception\Configuration;
use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\TestDrupalKernel;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class Drupal8Module
 * @package Codeception\Module
 */
class Drupal8 extends Module
{
    /**
     * A list of all of the available roles on our Drupal site.
     * @var \Drupal\Core\Entity\EntityInterface[]|static[]
     */
    protected $roles;

    /**
     * An output helper so we can add some custom output when tests run.
     * @var \Symfony\Component\Console\Output\ConsoleOutput
     */
    protected $output;

    /**
     * Drupal8Module constructor.
     */
    public function __construct(ModuleContainer $container, $config = null)
    {
        $this->config = array_merge(
          [
            'drupal_root' => Configuration::projectDir() . 'web',
            'site_path' => 'sites/test',
            'create_users' => true,
            'destroy_users' => true,
            'test_user_pass' => 'test'
          ],
          (array)$config
        );

        // Bootstrap a bare minimum Kernel so we can interact with Drupal.
        $autoloader = require_once Configuration::projectDir() . 'web/autoload.php';
        $kernel = new TestDrupalKernel('prod', $autoloader,
          $this->config['drupal_root']);
        $kernel->bootTestEnvironment($this->config['site_path']);

        // Allow for setting some basic info output.
        $this->output = new ConsoleOutput();
        // Get our role definitions as we use them a lot.
        $this->roles = Role::loadMultiple();

        parent::__construct($container);
    }

    /**
     * Setup Test environment.
     */
    public function _beforeSuite($settings = [])
    {
        if ($this->config['create_users']) {
            $this->scaffoldTestUsers();
        }
    }

    /**
     * Tear down after tests.
     */
    public function _afterSuite()
    {
        if ($this->config['destroy_users']) {
            $this->tearDownTestUsers();
        }
    }

    /**
     * Create a test user based on a role.
     *
     * @param string $role
     *
     * @return $this
     */
    public function createTestUser($role = 'administrator')
    {
        if ($role != 'anonymous' && !$this->userExists($role)) {
            $this->output->writeln("creating test{$role}User...");
            User::create([
              'name' => "test{$role}User",
              'mail' => "test{$role}User@example.com",
              'roles' => [$role],
              'pass' => $this->config['test_user_pass'],
              'status' => 1,
            ])->save();
        }
        return $this;
    }

    /**
     * Destroy a user that matches a test user name.
     *
     * @param $role
     * @return $this
     */
    public function destroyTestUser($role)
    {
        $this->output->writeln("deleting test{$role}User...");
        $users = \Drupal::entityQuery('user')
                        ->condition("name", "test{$role}User")
                        ->execute();

        array_map('user_delete', array_keys($users));
        return $this;
    }

    /**
     * Create a test user for each role in Drupal database.
     *
     * @return $this
     */
    public function scaffoldTestUsers()
    {
        array_map([$this, 'createTestUser'], array_keys($this->roles));
        return $this;
    }

    /**
     * Remove all users matching test user names.
     *
     * @return $this
     */
    public function tearDownTestUsers()
    {

        array_map([$this, 'destroyTestUser'], array_keys($this->roles));
        return $this;
    }

    /**
     * @param $role
     * @return bool
     */
    private function userExists($role)
    {
        return !empty(\Drupal::entityQuery('user')
                             ->condition('name', "test{$role}User")
                             ->execute());
    }
}