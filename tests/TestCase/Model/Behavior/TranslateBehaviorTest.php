<?php
namespace Trois\Utils\Test\TestCase\Model\Behavior;

use Cake\TestSuite\TestCase;
use Trois\Utils\Model\Behavior\TranslateBehavior;

/**
 * Trois\Utils\Model\Behavior\TranslateBehavior Test Case
 */
class TranslateBehaviorTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \Trois\Utils\Model\Behavior\TranslateBehavior
     */
    public $Translate;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->Translate = new TranslateBehavior();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Translate);

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
