<?php

namespace Liip\Drupal\Modules\Registry\Drupal\Database;

use Liip\Drupal\Modules\Registry\Database\MySql;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;


class MySqlTest extends RegistryTestCase
{
    /**
     * @param array $methods
     *
     * @return \PDOStatement|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDBResult(array $methods = array())
    {
        return $this->getMockBuilder('\PDOStatement')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDatabase(array $methods = array())
    {
        return $this->getMockbuilder('\PDO')
            ->setConstructorArgs(array('mysql:host=localhost'))
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::__construct
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::init
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::registryTableExists
     */
    public function testInit()
    {
        $expected = array('myregistry' => array());

        $result = $this->getDBResult(array('fetchAll'));
        $result
            ->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(array()));

        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->exactly(2))
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->exactly(2))
            ->method('query')
            ->will($this->onConsecutiveCalls(
                true,
                $result
            ));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);
        $registry->init();

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::init
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::registryTableExists
     */
    public function testInitExpectingException()
    {
        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('query')
            ->will($this->returnValue(false));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->init();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::destroy
     */
    public function testDestroy()
    {
        $database = $this->getDatabase(array('quote', 'exec'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('exec')
            ->will($this->returnValue(true));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);
        $registry->destroy();

        $attrib = $this->readAttribute($registry, 'registry');
        $this->assertEmpty($attrib['myregistry']);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::destroy
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::throwException
     */
    public function testDestroyExpectingException()
    {
        $database = $this->getDatabase(array('quote', 'exec'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('exec')
            ->will($this->returnValue(false));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->destroy();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::getContent
     */
    public function testContent()
    {
        $result = $this->getDBResult(array('fetchAll'));
        $result
            ->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(array()));

        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT * FROM `myregistry`;'))
            ->will($this->returnValue($result));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->assertEquals(array(), $registry->getContent());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::getContent
     */
    public function testContentExpectingException()
    {
        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT * FROM `myregistry`;'))
            ->will($this->returnValue(false));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->getContent();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::getContentById
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::getContentByIds
     */
    public function testContentById()
    {
        $result = $this->getDBResult(array('fetchAll'));
        $result
            ->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(array('entityId' => 'foo')));

        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT * FROM myregistry WHERE entityId IN (`foo`);'))
            ->will($this->returnValue($result));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->assertEquals(array('entityId' => 'foo'), $registry->getContentById('foo'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::getContentById
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::getContentByIds
     */
    public function testContentByIdProvidingDefaultValue()
    {
        $result = $this->getDBResult(array('fetchAll'));
        $result
            ->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(array()));

        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT * FROM myregistry WHERE entityId IN (`foo`);'))
            ->will($this->returnValue($result));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->assertEquals(
            array('entityId' => 'default'),
            $registry->getContentById('foo', array('entityId' => 'default'))
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::getContentByIds
     */
    public function testContentByIdsExpectingException()
    {
        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT * FROM myregistry WHERE entityId IN (`foo`);'))
            ->will($this->returnValue(false));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->getContentById('foo');
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::register
     */
    public function testRegister()
    {
        $result = $this->getDBResult();
        $result
            ->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue(array()));

        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->exactly(3))
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->exactly(2))
            ->method('query')
            ->will($this->returnValue($result));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $registry->register('tux', json_encode(array()));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::register
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::isRegistered
     */
    public function testRegisterExpectingException()
    {
        $database = $this->getDatabase(array('quote', 'query'));
        $database
            ->expects($this->once())
            ->method('quote')
            ->will($this->returnArgument(0));
        $database
            ->expects($this->once())
            ->method('query')
            ->will( $this->returnValue(false));

        $assertion = $this->getAssertionObjectMock();

        $registry = new MySql('MyRegistry', $assertion, $database);

        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');
        $registry->register('tux', json_encode(array()));
    }
}
