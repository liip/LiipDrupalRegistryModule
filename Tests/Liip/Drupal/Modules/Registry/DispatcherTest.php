<?php
/**
 * @file
 *   test suite to validate the correct implementation of the dispatcher functionality.
 */
namespace Liip\Drupal\Modules\Registry;

use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;


/**
 * Class DispatcherTest
 * @package LiipDrupalModulesRegistry
 */
class DispatcherTest extends RegistryTestCase
{
    /**
     * Provides a fake of the Registry class.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Liip\Drupal\Modules\Registry\Registry
     */
    protected function getRegistryFake()
    {
        $registry = $this->getMockBuilder('\Liip\Drupal\Modules\Registry\Registry')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        return $registry;
    }

    /**
     * Provides a stub of the Registry class.
     *
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Liip\Drupal\Modules\Registry\Registry
     */
    protected function getRegistryMock(array $methods = array())
    {
        return $this->getMockBuilder('\Liip\Drupal\Modules\Registry\Registry')
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMockForAbstractClass();
    }


    /**
     * @dataProvider registryProvider
     * @covers \Liip\Drupal\Modules\Registry\Dispatcher::attach
     */
    public function testAttach($id)
    {
        $registry = $this->getRegistryFake();

        $dispatcher = new Dispatcher();
        $dispatcher->attach($registry, $id);

        $this->assertAttributeEquals(
            array(
                $id => $registry,
            ),
            'registries',
            $dispatcher
        );
    }
    public function registryProvider()
    {
        return array(
            'id as integer (id > 0)' => array(42),
            'id as integer (id == 0)' => array(0),
            'id as string' => array('tux'),
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Dispatcher::attach
     */
    public function testAttachExpectingException()
    {
        $registry = $this->getRegistryFake();

        $dispatcher = new Dispatcher();
        $dispatcher->attach($registry, 'Tux');

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $dispatcher->attach($registry, 'Tux');
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Dispatcher::detach
     */
    public function testDetach()
    {
        $dispatcher = new Dispatcher();
        $dispatcher->attach($this->getRegistryFake(), 'TuxRegistry');
        $dispatcher->detach('TuxRegistry');

        $this->assertAttributeEmpty('registries', $dispatcher);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Dispatcher::detach
     */
    public function testDetachExpectingException()
    {
        $dispatcher = new Dispatcher();
        $dispatcher->attach($this->getRegistryFake(), 'TuxRegistry');

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $dispatcher->detach('Unknown Registry');
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Dispatcher::dispatch
     */
    public function testDispatch()
    {
        $registry = $this->getRegistryMock(array('register'));
        $registry
            ->expects($this->exactly(2))
            ->method('register')
            ->with(
                $this->isType('string'),
                $this->isType('array')
            )
            ->will($this->onConsecutiveCalls(
                true,
                true
            ));

        $dispatcher = new Dispatcher();
        $dispatcher->attach($registry, 'Tux');
        $dispatcher->attach($registry, 'Gnu');
        $output = $dispatcher->dispatch('register', 'registryId', array('id' => 'registryId'));

        $this->assertContainsOnly('boolean', $output);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Dispatcher::hasError
     * @covers \Liip\Drupal\Modules\Registry\Dispatcher::getLastErrors
     */
    public function testErrorHandling()
    {
        $registry = $this->getRegistryMock(array('register'));
        $registry
            ->expects($this->exactly(2))
            ->method('register')
            ->will($this->onConsecutiveCalls(
                true,
                $this->throwException(new RegistryException('error occurred.'))
            ));

        $dispatcher = new Dispatcher();
        $dispatcher->attach($registry, 'Tux');
        $dispatcher->attach($registry, 'Gnu');
        $dispatcher->dispatch('register', 'registryId', array('id' => 'registryId'));

        $this->assertTrue($dispatcher->hasError());
        $this->assertArrayHasKey('Gnu', $dispatcher->getLastErrors());
    }
}
