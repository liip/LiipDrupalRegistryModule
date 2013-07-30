<?php
namespace Liip\Drupal\Modules\Registry\Memory;

use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class PopoTest extends RegistryTestCase
{
    /**
     * Provides an instance of the sut.
     * @return Popo
     */
    protected function getPopoObject()
    {
        $registry = new Popo(
            'mySection',
            $this->getAssertionObjectMock()
        );

        return $registry;
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::destroy
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::__construct
     */
    public function testDestroy()
    {
        $registry = $this->getPopoObject();

        $registry->register('Tux', 'devil');
        $registry->destroy();

        $this->assertAttributeEquals(array(), 'registry', $registry);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::init
     */
    public function testInit()
    {
        $registry = $this->getPopoObject();

        $registry->init();

        $this->assertAttributeEquals(array('mySection' => array()), 'registry', $registry);
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::init
     */
    public function testInitExpectingException()
    {
        $registry = $this->getPopoObject();

        $registry->register('Darwin', 'Evolution works!');
        $registry->init();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::getContent
     */
    public function testGetContent()
    {
        $registry = $this->getPopoObject();

        $registry->register('Tux', array('devil', 'Beastie'));

        $this->assertEquals(
            array('Tux' => array('devil', 'Beastie')),
            $registry->getContent()
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::getContentById
     */
    public function testGetContentById()
    {
        $registry = $this->getPopoObject();

        $registry->register('Tux', array('devil', 'Beastie'));

        $this->assertEquals(
            array('devil', 'Beastie'),
            $registry->getContentById('Tux')
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::register
     */
    public function testRegister()
    {
        $registry = $this->getPopoObject();

        $registry->register('Tux', array('devil', 'Beastie'));

        $this->assertAttributeEquals(
            array('mySection' =>
                  array(
                      'Tux' => array('devil', 'Beastie')
                  )
            ),
            'registry',
            $registry
        );
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::register
     */
    public function testRegisterExpectingException()
    {
        $registry = $this->getPopoObject();

        $registry->register('Tux', array('devil', 'Beastie'));
        $registry->register('Tux', array('Beastie'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::replace
     */
    public function testReplace()
    {
        $registry = $this->getPopoObject();

        $registry->register('Tux', array('devil', 'Beastie'));
        $registry->replace('Tux', array('Beastie'));

        $this->assertAttributeEquals(
            array('mySection' =>
                  array(
                      'Tux' => array('Beastie')
                  )
            ),
            'registry',
            $registry
        );
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::replace
     */
    public function testReplaceExpectingException()
    {
        $registry = $this->getPopoObject();

        $registry->replace('Tux', array('Beastie'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::unregister
     */
    public function testUnregister()
    {
        $registry = new Popo(
            'mySection',
            $this->getAssertionObjectMock()
        );
        $registry->register('Tux', array('devil', 'Beastie'));
        $registry->unregister('Tux');

        $this->assertAttributeEquals(
            array('mySection' => array()),
            'registry',
            $registry
        );
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::unregister
     */
    public function testUnregisterExpectingExcetion()
    {
        $registry = $this->getPopoObject();

        $registry->unregister('Tux');
    }
    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::isRegistered
     */
    public function testIsRegistered()
    {
        $registry = $this->getPopoObject();

        $this->assertFalse($registry->isRegistered('Tux'));
    }

    /**
     * @covers \stdClass
     */
    public function testLiveCycle()
    {
        $registry = $this->getPopoObject();

        $registry->init();
        $this->assertAttributeEquals(array('mySection' => array()), 'registry', $registry);

        $registry->register('Devil', array('os' => 'Linux'));
        $this->assertAttributeEquals(
            array(
                'mySection' => array('Devil' => array('os' => 'Linux'))
            ),
            'registry',
            $registry
        );

        $data = $registry->getContent();
        $this->assertEquals(array('Devil' => array('os' => 'Linux')), $data);

        $entry = $registry->getContentById('Devil');
        $this->assertEquals(array('os' => 'Linux'), $entry);

        $registry->unregister('Devil');
        $this->assertAttributeEquals(array('mySection' => array()), 'registry', $registry);

        $registry->destroy();
        $this->assertAttributeEquals(array(), 'registry', $registry);


    }
}
