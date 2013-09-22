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
     * @param $section
     *
     * @return Multiply
     */
    protected function getMultiplyObject($section = 'testSection')
    {
        $assertion = $this->getAssertionObjectMock(array('notEmpty'));

        $registries = array(
            'D7Config',
            'Popo',
        );

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
            ->getMockForAbstractClass();

        return $factory;
    }

    /**
     * Provides an instance of an implementation of the RegistryInterface
     *
     * @param string $class
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function getRegistryStub($class, array $methods = array())
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
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
}
