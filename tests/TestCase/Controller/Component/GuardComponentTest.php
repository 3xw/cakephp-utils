<?php
namespace Trois\Utils\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Trois\Utils\Controller\Component\GuardComponent;

/**
 * Trois\Utils\Controller\Component\GuardComponent Test Case
 */
class GuardComponentTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Trois\Utils\Controller\Component\GuardComponent
     */
    public $Guard;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->Guard = new GuardComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Guard);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
