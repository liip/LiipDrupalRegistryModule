<?php
namespace netmigrosintranet\modules\Registry\Classes\Drupal;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use netmigrosintranet\modules\Registry\Tests\RegistryTestCase;

class D7ConfigTest extends RegistryTestCase
{
    /**
     * Provides a proxied representation of the Registry class.
     *
     * @return \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config
     */
    protected function getD7ConfigProxy(array $methods = array(), array $assertionMethods = array())
    {
        return $this->getProxyBuilder('\\netmigrosintranet\\modules\\Registry\\Classes\\Drupal\\D7Config')
            ->setProperties(array('registry'))
            ->setConstructorArgs(array(
                'mySection',
                $this->getDrupalCommonConnectorFixture($methods),
                $this->getAssertionObjectMock($assertionMethods)
                ))
            ->getProxy();
    }


    /**
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::verifySectionName
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
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

        $registry = $this->getProxyBuilder('\\netmigrosintranet\\modules\\Registry\\Classes\\Drupal\\D7Config')
            ->setMethods(array('verifySectionName'))
            ->setProperties(array('drupalCommonConnector', 'assertion'))
            ->disableOriginalConstructor()
            ->getProxy();

        $registry->drupalCommonConnector = $this->getDrupalCommonConnectorMock();
        $registry->assertion = $assertions;

        $registry->verifySectionName('mySection');

    }

    /**
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::register
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
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
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::replace
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
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
     * @expectedException \netmigrosintranet\modules\Registry\Classes\RegistryException
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::replace
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
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
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::unregister
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
     */
    public function testUnregister()
    {
        $registry = $this->getD7ConfigProxy(array('variable_set'), array('string', 'notEmpty'));

        $registry->registry = array('WorldOfOs' => array());
        $registry->unregister('WorldOfOs');

        $this->assertAttributeEmpty('registry', $registry);
    }

    /**
     * @expectedException \netmigrosintranet\modules\Registry\Classes\RegistryException
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::unregister
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
     */
    public function testUnregisterExpectingRegistryException()
    {
        $registry = $this->getD7ConfigProxy(array('t'), array('string', 'notEmpty'));
        $registry->unregister('WorldOfOs');
    }

    /**
     * @expectedException \netmigrosintranet\modules\Registry\Classes\RegistryException
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::register
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
     */
    public function testRegisterDuplicateWorldIdentifier()
    {
        $registry = $this->getD7ConfigProxy(array('t'));

        $registry->registry = array('WorldOfOs' => array());
        $registry->register('WorldOfOs', array());
    }

    /**
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::isRegistered
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::__construct
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
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::getContent
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
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::getContentById
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
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::destroy
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

    /**
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::init
     */
    public function testInit()
    {
        $connector = $this->getDrupalCommonConnectorFixture(array('variable_set'));
        $connector
            ->expects($this->once())
            ->method('variable_get')
            ->will($this->returnValue(array()));

        $connector
            ->expects($this->once())
            ->method('variable_set')
            ->with(
                $this->isType('string'),
                $this->isType('array')
            );

        $registry = new D7Config(
            'mySection',
            $connector,
            $this->getAssertionObjectMock()
        );

        $registry->init();
    }

    /**
     * @expectedException \netmigrosintranet\modules\Registry\Classes\RegistryException
     * @dataProvider initExpectingRegistryExceptionDataProvider
     * @covers \netmigrosintranet\modules\Registry\Classes\Drupal\D7Config::init
     */
    public function testInitExpectingRegistryException($data)
    {
        $connector = $this->getDrupalCommonConnectorMock(array('t', 'variable_get'));
        $connector
            ->expects($this->once())
            ->method('variable_get')
            ->will($this->returnValue($data));
        $connector
            ->expects($this->once())
            ->method('t')
            ->with($this->isType('string'));

        $registry = new D7Config(
            'mySection',
            $connector,
            $this->getAssertionObjectMock(array('string', 'notEmpty'))
        );

        $registry->init();
    }
    public static function initExpectingRegistryExceptionDataProvider()
    {
        return array(
            'simple array' => array(array('tux')),
            'simple assoc. array' => array(array('beastie' => 'tux')),
            'object' => array(new \stdClass),
        );
    }
}
