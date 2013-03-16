<?php
namespace Liip\Drupal\Modules\Registry\Memory;

use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class PopoTest extends RegistryTestCase
{
    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::destroy
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::__construct
     */
    public function testDestroy()
    {
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

        $registry->register('Tux', 'devil');
        $registry->destroy();

        $this->assertAttributeEquals(array(), 'registry', $registry);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::init
     */
    public function testInit()
    {
        $registry = new Popo(
            'Tux',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );
        $registry->init();

        $this->assertAttributeEquals(array('Tux' =>  array()), 'registry', $registry);
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::init
     */
    public function testInitExpectingException()
    {
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );
        $registry->register('Darwin', 'Evolution works!');
        $registry->init();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::getContent
     */
    public function testGetContent()
    {
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

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
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

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
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

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
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

        $registry->register('Tux', array('devil', 'Beastie'));
        $registry->register('Tux', array('Beastie'));
    }

   /**
    * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::replace
    */
    public function testReplace()
    {
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

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
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

        $registry->replace('Tux', array('Beastie'));
    }

   /**
    * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::unregister
    */
    public function testUnregister()
    {
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
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
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

        $registry->unregister('Tux');
    }

   /**
    * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::isRegistered
    */
    public function testIsRegistered()
    {
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

        $this->assertFalse($registry->isRegistered('Tux'));
    }

    /**
     * @covers \stdClass
     */
    public function testLiveCycle()
    {
        $registry = new Popo(
            'Tux',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

        $registry->init();
        $this->assertAttributeEquals(array('Tux' => array()), 'registry', $registry);

        $registry->register('Devil', array('os' => 'Linux'));
        $this->assertAttributeEquals(
            array(
                'Tux' => array('Devil' => array('os' => 'Linux'))
            ),
            'registry',
            $registry
        );

        $data = $registry->getContent();
        $this->assertEquals(array('Devil' => array('os' => 'Linux')), $data);

        $entry = $registry->getContentById('Devil');
        $this->assertEquals(array('os' => 'Linux'), $entry);

        $registry->unregister('Devil');
        $this->assertAttributeEquals(array('Tux' => array()), 'registry', $registry);

        $registry->destroy();
        $this->assertAttributeEquals(array(), 'registry', $registry);


    }
}
