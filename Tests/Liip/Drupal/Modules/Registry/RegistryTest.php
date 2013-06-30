<?php
namespace Liip\Drupal\Modules\Registry\Drupal;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class RegistryTest extends RegistryTestCase
{
    /**
     * @param \Assert\Assertion $assertions
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Liip\Drupal\Modules\Registry\Registry
     */
    protected function getRegistryObject(Assertion $assertions)
    {
        return $this->getMockBuilder('\\Liip\\Drupal\\Modules\\Registry\\Registry')
            ->setMethods(array('destroy'))
            ->setConstructorArgs(array(
                'mySection',
                $this->getDrupalCommonConnectorMock(),
                $assertions
            ))
            ->getMockForAbstractClass();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::verifySectionName
     * @covers \Liip\Drupal\Modules\Registry\Registry::__construct
     */
    public function testVerifySectionName()
    {
        $assertions = $this->getMockBuilder('\\Assert\\Assertion')
            ->setMockClassName('SpecialSubForVerifySectionName')
            ->setMethods(array('string', 'notEmpty'))
            ->getMock();
        $assertions
            ->staticExpects($this->once())
            ->method('string')
            ->with($this->isType('string'));
        $assertions
            ->staticExpects($this->once())
            ->method('notEmpty')
            ->with($this->isType('string'));

        $registry = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Drupal\\D7Config')
            ->setMethods(array('verifySectionName'))
            ->setProperties(array('drupalCommonConnector', 'assertion'))
            ->disableOriginalConstructor()
            ->getProxy();

        $registry->drupalCommonConnector = $this->getDrupalCommonConnectorMock();
        $registry->assertion = $assertions;

        $registry->verifySectionName('mySection');
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::register
     * @covers \Liip\Drupal\Modules\Registry\Registry::__construct
     */
    public function testRegister()
    {
        $expected = array(
            'mySection' => array (
                'WorldOfOs' => array('TinMan', 'lion'),
            )
        );

        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = $this->getRegistryObject($assertions);

        $registry->register('WorldOfOs', array('TinMan', 'lion'));

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::replace
     */
    public function testReplace()
    {
        $expected = array(
            'mySection' => array(
                'WorldOfOs' => array('TUX'),
            )
        );

        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);
        $registry->register('WorldOfOs', array());
        $registry->replace('WorldOfOs', array('TUX'));

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Registry::replace
     */
    public function testReplaceExpectingRegistryException()
    {
        $registry = $this->getRegistryObject($this->getAssertionObjectMock());

        $registry->replace('WorldOfOs', array());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::unregister
     */
    public function testUnregister()
    {
        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);
        $registry->register('WorldOfOs', array());
        $registry->unregister('WorldOfOs');

        $content = $this->readAttribute($registry, 'registry');

        $this->assertEmpty($content['mySection']);
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Registry::unregister
     */
    public function testUnregisterExpectingRegistryException()
    {
        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);
        $registry->unregister('WorldOfOs');
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Registry::register
     */
    public function testRegisterDuplicateWorldIdentifier()
    {
        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);
        $registry->register('WorldOfOs', array());
        $registry->register('WorldOfOs', array());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::isRegistered
     */
    public function testIsRegistered()
    {
        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);

        $this->assertFalse($registry->isRegistered('Tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::getContent
     */
    public function testGetContent()
    {
        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);
        $registry->register('worldOfOs', array());

        $this->assertArrayHasKey('worldOfOs', $registry->getContent());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::getContentById
     */
    public function testGetContentById()
    {
        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);
        $registry->register('worldOfOs', array());

        $this->assertInternalType('array', $registry->getContentById('worldOfOs'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Registry::getContentByIds
     */
    public function testGetContentByIds()
    {
        $assertions = $this->getAssertionObjectMock();

        $registry = $this->getRegistryObject($assertions);
        $registry->register('worldOfOs', array());
        $registry->register('worldOfWarcraft', array());
        $registry->register('worldOfWisdom', array());

        $this->assertEquals(
            array('worldOfOs' => array(), 'worldOfWarcraft' => array()),
            $registry->getContentByIds(array('worldOfOs', 'worldOfWarcraft'))
        );
    }
}
