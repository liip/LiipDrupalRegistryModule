<?php
/**
 * @file
 *   test suite to validate the correct implementation of the Multiplier registry.
 */

namespace Liip\Drupal\Modules\Registry;


use Assert\Assertion;
use Assert\InvalidArgumentException;
use Liip\Drupal\Modules\Registry\Multiply;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class MultiplyTest extends RegistryTestCase
{
    /**
     * Provides an instance of the Multiply registry.
     *
     * @param string $section
     * @param array $registries
     *
     * @return Multiply
     */
    protected function getMultiplyObject($section = 'testSection', $registries = array('D7Config','Popo'))
    {
        $assertion = $this->getAssertionObjectMock(array('notEmpty'));

        return new Multiply($section, $assertion, $registries);
    }

    /**
     * @return Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFactoryReturningNoContent()
    {
        $d7Registry = $this->getRegistryStub('\Liip\Drupal\Modules\Registry\Drupal\D7Config');
        $d7Registry
            ->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(array()));

        $popoRegistry = $this->getRegistryStub('\Liip\Drupal\Modules\Registry\Memory\Popo');
        $popoRegistry
            ->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(array()));

        $factory = $this->getFactoryStub(array('getRegistry'));
        $factory
            ->expects($this->exactly(3))
            ->method('getRegistry')
            ->will(
                $this->onConsecutiveCalls(
                    $d7Registry,
                    $popoRegistry,
                    $this->throwException(new InvalidArgumentException('Test to stop.', Assertion::VALUE_EMPTY))
                )
            );

        return $factory;
    }

    /**
     * @param $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Liip\Drupal\Modules\Registry\Factory
     */
    protected function getFactoryStub(array $methods = array())
    {
        $factory = $this->getMockBuilder('\Liip\Drupal\Modules\Registry\Factory')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();

        return $factory;
    }

    /**
     * @param $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDispatcherStub(array $methods = array())
    {
        return $this->getMockBuilder('\Liip\Drupal\Modules\Registry\Dispatcher')
            ->setMethods($methods)
            ->getMock();
    }


    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::getContent
     * @covers \Liip\Drupal\Modules\Registry\Multiply::__construct
     * @covers \Liip\Drupal\Modules\Registry\Multiply::readUntilNotEmpty
     */
    public function testGetContent()
    {
        $multiple = $this->getMultiplyObject();
        $multiple->setFactory($this->getFactoryReturningNoContent());

        $this->assertEquals(array(), $multiple->getContent());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::getContentById
     * @covers \Liip\Drupal\Modules\Registry\Multiply::__construct
     * @covers \Liip\Drupal\Modules\Registry\Multiply::readUntilNotEmpty
     */
    public function testGeContentByIdReturningDefault()
    {
        $multiple = $this->getMultiplyObject();
        $multiple->setFactory($this->getFactoryReturningNoContent());

        $this->assertNull($multiple->getContentById('Tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::getContentById
     * @covers \Liip\Drupal\Modules\Registry\Multiply::__construct
     * @covers \Liip\Drupal\Modules\Registry\Multiply::readUntilNotEmpty
     */
    public function testGetContentById()
    {
        $registry = $this->getRegistryStub('\Liip\Drupal\Modules\Registry\Memory\Popo');
        $registry
            ->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue(array('tux' => array('id' => 'tux'))));


        $factory = $this->getFactoryStub(array('getRegistry'));
        $factory
            ->expects($this->once())
            ->method('getRegistry')
            ->will($this->returnValue($registry));

        $multiple = $this->getMultiplyObject();
        $multiple->setFactory($factory);

        $this->assertEquals(array('id' => 'tux'), $multiple->getContentById('tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::register
     */
    public function testRegister()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                'register',
                $this->isType('string'),
                $this->isType('array')
            );
        $dispatcher
            ->expects($this->once())
            ->method('hasError')
            ->will($this->returnValue(false));

        $multiply = $this->getMultiplyObject();
        $multiply->setDispatcher($dispatcher);

        $multiply->register('Tux', array('id' => 'tux'));

        $this->assertEquals(array('id' => 'tux'), $multiply->getContentById('Tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::register
     */
    public function testRegisterExpectingException()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->exactly(2))
            ->method('hasError')
            ->will($this->returnValue(true));

        $multiply = $this->getMultiplyObject();
        $multiply->setDispatcher($dispatcher);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');

        $multiply->register('Tux', array('id' => 'tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::replace
     */
    public function testReplace()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->exactly(2))
            ->method('hasError')
            ->will($this->returnValue(false));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);

        $multiply->register('Tux', array('id' => 'tux'));
        $multiply->replace('Tux', array('id' => 'tux', 'data' => array()));

        $this->assertEquals(array('id' => 'tux', 'data' => array()), $multiply->getContentById('Tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::replace
     */
    public function testReplaceExpectingException()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isType('string'),
                $this->isType('string'),
                $this->isType('array')
            );
        $dispatcher
            ->expects($this->exactly(2))
            ->method('hasError')
            ->will($this->returnValue(true));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');

        $multiply->replace('Tux', array('id' => 'tux', 'data' => array()));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::unregister
     */
    public function testUnregister()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->exactly(2))
            ->method('hasError')
            ->will($this->returnValue(false));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);

        $multiply->register('Tux', array('id' => 'tux'));
        $multiply->unregister('Tux');

        $this->assertFalse($multiply->isRegistered('Tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::unregister
     */
    public function testUnregisterExpectingException()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->exactly(2))
            ->method('hasError')
            ->will($this->returnValue(true));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');

        $multiply->unregister('Tux');
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::isRegistered
     */
    public function testIsRegistered()
    {
        $multiple = $this->getMultiplyObject();
        $multiple->setFactory($this->getFactoryReturningNoContent());

        $this->assertFalse($multiple->isRegistered('tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::isRegistered
     */
    public function testIsRegisteredFromCache()
    {
        $multiple = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Multiply')
            ->setConstructorArgs(array('testSection', $this->getAssertionObjectMock(), array('D7Config','Popo')))
            ->setProperties(array('registry'))
            ->getProxy();
        $multiple->registry = array('testSection' => array('tux' => array()));
        $multiple->setFactory($this->getFactoryStub());

        $this->assertTrue($multiple->isRegistered('tux'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::getLogger
     * @covers \Liip\Drupal\Modules\Registry\Multiply::setLogger
     */
    public function testLogger()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface $logger */
        $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')
            ->getMockForAbstractClass();

        $multiple = $this->getMultiplyObject();

        $this->assertInstanceOf('\Psr\Log\NullLogger', $multiple->getLogger());

        $multiple->setLogger($logger);
        $this->assertSame($logger, $multiple->getLogger());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::getFactory
     * @covers \Liip\Drupal\Modules\Registry\Multiply::setFactory
     */
    public function testFactory()
    {
        $factory = $this->getFactoryStub();

        $multiple = $this->getMultiplyObject();

        $this->assertInstanceOf('\Liip\Drupal\Modules\Registry\Factory', $multiple->getFactory());

        $multiple->setFactory($factory);
        $this->assertSame($factory, $multiple->getFactory());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::getDispatcher
     * @covers \Liip\Drupal\Modules\Registry\Multiply::setDispatcher
     */
    public function testDispatcher()
    {
        $dispatcher = $this->getDispatcherStub();
        $factory = $this->getFactoryStub(array('getRegistry'));
        $factory
            ->expects($this->once())
            ->method('getRegistry')
            ->will($this->returnValue($this->getRegistryStub('\Liip\Drupal\Modules\Registry\Memory\Popo')));

        $multiple = $this->getMultiplyObject('testSection', array('Popo'));
        $multiple->setFactory($factory);

        $this->assertInstanceOf('\Liip\Drupal\Modules\Registry\Dispatcher', $multiple->getDispatcher());

        $multiple->setDispatcher($dispatcher);
        $this->assertSame($dispatcher, $multiple->getDispatcher());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::init
     */
    public function testInit()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->once())
            ->method('dispatch');
        $dispatcher
            ->expects($this->once())
            ->method('hasError')
            ->will($this->returnValue(false));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);
        $multiply->init();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::init
     */
    public function testInitExpectingException()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->once())
            ->method('dispatch');
        $dispatcher
            ->expects($this->exactly(2))
            ->method('hasError')
            ->will($this->returnValue(true));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');

        $multiply->init();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::destroy
     */
    public function testDestroy()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->once())
            ->method('dispatch');
        $dispatcher
            ->expects($this->once())
            ->method('hasError')
            ->will($this->returnValue(false));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);
        $multiply->destroy();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Multiply::destroy
     */
    public function testDestroyExpectingException()
    {
        $dispatcher = $this->getDispatcherStub(array('dispatch', 'hasError'));
        $dispatcher
            ->expects($this->once())
            ->method('dispatch');
        $dispatcher
            ->expects($this->exactly(2))
            ->method('hasError')
            ->will($this->returnValue(true));

        $multiply = $this->getMultiplyObject('testSection', array('Popo'));
        $multiply->setDispatcher($dispatcher);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');

        $multiply->destroy();
    }
}
