<?php
namespace Liip\Drupal\Modules\Registry\Drupal;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class D7ConfigTest extends RegistryTestCase
{
    /**
     * Provides a proxied representation of the Registry class.
     *
     * @return \Liip\Drupal\Modules\Registry\Drupal\D7Config
     */
    protected function getD7ConfigProxy(array $methods = array(), array $assertionMethods = array())
    {
        return $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Drupal\\D7Config')
            ->setProperties(array('registry'))
            ->setConstructorArgs(array(
                'mySection',
                $this->getDrupalCommonConnectorFixture($methods),
                $this->getAssertionObjectMock($assertionMethods)
                ))
            ->getProxy();
    }


    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::verifySectionName
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
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
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::register
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testRegister()
    {
        $expected = array(
            'WorldOfOs' => array(),
        );

        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = new D7Config(
            'mySection',
            $this->getDrupalCommonConnectorFixture(array('variable_set')),
            $assertions
        );

        $registry->register('WorldOfOs', array());

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::replace
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testReplace()
    {
        $expected = array(
            'WorldOfOs' => array('TUX'),
        );

        $registry = $this->getD7ConfigProxy(
            array('variable_set'),
            array('string', 'notEmpty')
        );

        $registry->registry = array('WorldOfOs' => array());
        $registry->replace('WorldOfOs', array('TUX'));

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::replace
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testReplaceExpectingRegistryException()
    {
        $registry = new D7Config(
            'mySection',
            $this->getDrupalCommonConnectorFixture(array('t')),
            $this->getAssertionObjectMock(array('string', 'notEmpty'))
        );

        $registry->replace('WorldOfOs', array());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::unregister
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testUnregister()
    {
        $registry = $this->getD7ConfigProxy(array('variable_set'), array('string', 'notEmpty'));

        $registry->registry = array('WorldOfOs' => array());
        $registry->unregister('WorldOfOs');

        $this->assertAttributeEmpty('registry', $registry);
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::unregister
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testUnregisterExpectingRegistryException()
    {
        $registry = $this->getD7ConfigProxy(array('t'), array('string', 'notEmpty'));
        $registry->unregister('WorldOfOs');
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::register
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testRegisterDuplicateWorldIdentifier()
    {
        $registry = $this->getD7ConfigProxy(array('t'));

        $registry->registry = array('WorldOfOs' => array());
        $registry->register('WorldOfOs', array());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::isRegistered
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testIsRegistered()
    {
        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = new D7Config('mySection', $this->getDrupalCommonConnectorFixture(), $assertions);

        $this->assertFalse($registry->isRegistered('Tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::getContent
     */
    public function testGetContent()
    {
        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = new D7Config(
            'mySection',
            $this->getDrupalCommonConnectorFixture(array('variable_set')),
            $assertions
        );

        $registry->register('worldOfOs', array());

        $this->assertArrayHasKey('worldOfOs', $registry->getContent());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::getContentById
     */
    public function testGetContentById()
    {
        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = new D7Config(
            'mySection',
            $this->getDrupalCommonConnectorFixture(array('variable_set')),
            $assertions
        );

        $registry->register('worldOfOs', array());

        $this->assertInternalType('array', $registry->getContentById('worldOfOs'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::destroy
     */
    public function testDestroy()
    {
        $dcc =$this->getDrupalCommonConnectorMock(array('variable_del', 'variable_get'));
        $dcc
            ->expects($this->exactly(2))
            ->method('variable_get')
            ->will($this->returnValue(array()));

        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));

        $registry = new D7Config(
            'mySection',
            $dcc,
            $assertions
        );

        $registry->destroy();
    }
}
