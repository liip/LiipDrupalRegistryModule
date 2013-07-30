<?php
namespace Liip\Drupal\Modules\Registry\Drupal;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class D7ConfigTest extends RegistryTestCase
{
    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::register
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::__construct
     */
    public function testRegister()
    {
        $expected = array(
            'mySection' => array('WorldOfOs' => array(),)
        );

        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = new D7Config(
            'mySection',
            $assertions
        );
        $registry->setDrupalCommonConnector($this->getDrupalCommonConnectorFixture(array('variable_set')));

        $registry->register('WorldOfOs', array());

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }

    /**
     * Provides a fixture of the Common class of the Drupal Connector
     *
     * @param array $methods
     *
     * @return  \PHPUnit_Framework_MockObject_MockObject|\Liip\Drupal\Modules\DrupalConnector\Common
     */
    protected function getDrupalCommonConnectorFixture(array $methods = array())
    {
        $methods = array_merge($methods, array('variable_get'));

        $drupalCommonConnector = $this->getDrupalCommonConnectorMock($methods);
        $drupalCommonConnector
            ->expects($this->once())
            ->method('variable_get')
            ->with(
                $this->isType('string'),
                $this->isType('array')
            )
            ->will(
                $this->returnValue(array())
            );

        if (in_array('variable_set', $methods)) {
            $drupalCommonConnector
                ->expects($this->once())
                ->method('variable_set')
                ->with(
                    $this->isType('string')
                );
        }

        if (in_array('t', $methods)) {
            $drupalCommonConnector
                ->expects($this->once())
                ->method('t')
                ->with(
                    $this->isType('string')
                );
        }

        return $drupalCommonConnector;
    }

    /**
     * Provides a stub for the Common class of the DrupalConnector Module.
     *
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Liip\Drupal\Modules\DrupalConnector\Common
     */
    protected function getDrupalCommonConnectorMock(array $methods = array())
    {
        return $this->getMockBuilder('\\Liip\\Drupal\\Modules\\DrupalConnector\\Common')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::replace
     */
    public function testReplace()
    {
        $expected = array(
            'mySection' => array('WorldOfOs' => array('TUX'))
        );

        $dcc = $this->getDrupalCommonConnectorMock(array('variable_get', 'variable_set'));
        $dcc
            ->expects($this->once())
            ->method('variable_get')
            ->will(
                $this->returnValue(array('WorldOfOs' => array()))
            );

        $registry = $registry = new D7Config(
            'mySection',
            new Assertion()
        );
        $registry->setDrupalCommonConnector($dcc);

        $registry->replace('WorldOfOs', array('TUX'));

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::replace
     */
    public function testReplaceExpectingRegistryException()
    {
        $registry = new D7Config(
            'mySection',
            $this->getAssertionObjectMock(array('string', 'notEmpty'))
        );
        $registry->setDrupalCommonConnector($this->getDrupalCommonConnectorFixture());

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->replace('WorldOfOs', array());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::unregister
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::load
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
     */
    public function testUnregister()
    {
        $dcc = $this->getDrupalCommonConnectorMock(array('variable_get', 'variable_set'));
        $dcc
            ->expects($this->once())
            ->method('variable_get')
            ->will(
                $this->returnValue(array('WorldOfOs' => array()))
            );

        $registry = $registry = new D7Config(
            'mySection',
            new Assertion()
        );
        $registry->setDrupalCommonConnector($dcc);
        $registry->unregister('WorldOfOs');

        $content = $this->readAttribute($registry, 'registry');
        $this->assertEmpty($content['mySection']);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::unregister
     */
    public function testUnregisterExpectingRegistryException()
    {
        $registry = $this->getD7ConfigProxy(array(), array('string', 'notEmpty'));

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->unregister('WorldOfOs');
    }

    /**
     * Provides a proxied representation of the Registry class.
     *
     * @param array $methods
     * @param array $assertionMethods
     *
     * @return object D7Config
     */
    protected function getD7ConfigProxy(array $methods = array(), array $assertionMethods = array())
    {
        $registry = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Drupal\\D7Config')
            ->setProperties(array('registry'))
            ->setConstructorArgs(array(
                'mySection',
                $this->getAssertionObjectMock($assertionMethods)
            ))
            ->getProxy();
        $registry->setDrupalCommonConnector($this->getDrupalCommonConnectorFixture($methods));

        return $registry;
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::register
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::load
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
     */
    public function testRegisterDuplicateWorldIdentifier()
    {
        $dcc = $this->getDrupalCommonConnectorMock(array('t', 'variable_set', 'variable_get'));
        $dcc
            ->expects($this->once())
            ->method('variable_get')
            ->will(
                $this->returnValue(array('WorldOfOs' => array()))
            );

        $registry = $registry = new D7Config(
            'mySection',
            new Assertion()
        );
        $registry->setDrupalCommonConnector($dcc);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->register('WorldOfOs', array());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::isRegistered
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
     */
    public function testIsRegistered()
    {
        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = new D7Config('mySection', $assertions);
        $registry->setDrupalCommonConnector($this->getDrupalCommonConnectorMock());

        $this->assertFalse($registry->isRegistered('Tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::getContent
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
     */
    public function testGetContent()
    {
        $assertions = $this->getAssertionObjectMock(array('string', 'notEmpty'));
        $assertions
            ->staticExpects($this->exactly(2))
            ->method('string')
            ->with($this->isType('string'));

        $registry = $registry = new D7Config(
            'mySection',
            $assertions
        );
        $registry->setDrupalCommonConnector($this->getDrupalCommonConnectorFixture(array('variable_set')));

        $registry->register('worldOfOs', array());

        $this->assertArrayHasKey('worldOfOs', $registry->getContent());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::getContentById
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
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
            $assertions
        );
        $registry->setDrupalCommonConnector($this->getDrupalCommonConnectorFixture(array('variable_set')));

        $registry->register('worldOfOs', array());

        $this->assertInternalType('array', $registry->getContentById('worldOfOs'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::destroy
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
     */
    public function testDestroy()
    {
        $dcc = $this->getDrupalCommonConnectorMock(array('variable_del', 'variable_get'));
        $dcc
            ->expects($this->once())
            ->method('variable_get')
            ->will($this->returnValue(array()));

        $registry = new D7Config(
            'mySection',
            new Assertion()
        );
        $registry->setDrupalCommonConnector($dcc);

        $registry->destroy();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::destroy
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
     */
    public function testDestroyExpectingExeption()
    {
        $dcc = $this->getDrupalCommonConnectorMock(array('variable_del', 'variable_get'));
        $dcc
            ->expects($this->once())
            ->method('variable_get')
            ->will($this->returnValue(array('foo')));

        $registry = new D7Config(
            'mySection',
            new Assertion()
        );
        $registry->setDrupalCommonConnector($dcc);

        $this->setExpectedException('\InvalidArgumentException');
        $registry->destroy();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::init
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::load
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::setDrupalCommonConnector
     */
    public function testInit()
    {
        $dcc = $this->getDrupalCommonConnectorFixture(array('variable_set'));
        $dcc
            ->expects($this->once())
            ->method('variable_set')
            ->with(
                $this->isType('string'),
                $this->isType('array')
            );

        $registry = new D7Config('Tux', $this->getAssertionObjectMock());
        $registry->setDrupalCommonConnector($dcc);
        $registry->init();

        $this->assertAttributeEquals(array('Tux' => array()), 'registry', $registry);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::init
     */
    public function testInitExpectingException()
    {
        $dcc = $this->getDrupalCommonConnectorMock(array('t', 'variable_set', 'variable_get'));
        $dcc
            ->expects($this->once())
            ->method('variable_get')
            ->will(
                $this->returnValue(array('WorldOfOs' => array()))
            );

        $registry = $registry = new D7Config(
            'mySection',
            new Assertion()
        );
        $registry->setDrupalCommonConnector($dcc);


        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->init();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Drupal\D7Config::getDrupalCommonConnector
     */
    public function testGetDrupalCommonConnector()
    {
        $registry = new D7Config('mySection', new Assertion());

        $this->assertInstanceOf('\Liip\Drupal\Modules\DrupalConnector\Common', $registry->getDrupalCommonConnector());
    }
}
