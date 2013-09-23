<?php

namespace Liip\Drupal\Modules\Registry;


use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Database\MySql;
use Liip\Drupal\Modules\Registry\Drupal\D7Config;
use Liip\Drupal\Modules\Registry\Lucene\Elasticsearch;
use Liip\Drupal\Modules\Registry\Memory\Popo;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;
use Liip\Registry\Adaptor\Decorator\NoOpDecorator;

class FactoryTest extends RegistryTestCase
{
    /**
     * @covers \Liip\Drupal\Modules\Registry\Factory::getRegistry
     */
    public function testGetRegistryFromCache()
    {
        $factory = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Factory')
            ->setProperties(array('instances'))
            ->getProxy();
        $factory->instances = array('Popo' => $this->getRegistryStub('\Liip\Drupal\Modules\Registry\Memory\Popo'));

        $registry = $factory->getRegistry('Popo', 'testSection', new Assertion());

        $this->assertInstanceOf('\Liip\Drupal\Modules\Registry\Memory\Popo', $registry);
    }

    /**
     * @dataProvider registryNameProvider
     * @covers \Liip\Drupal\Modules\Registry\Factory::getRegistry
     * @covers \Liip\Drupal\Modules\Registry\Factory::getInstanceOf
     */
    public function testGetInstanceOf($expected, $name)
    {
        $config = array(
            'database' => array('dsn' => 'mysql:host=localhost'),
            'decorator' => array('name' =>'\Liip\Registry\Adaptor\Decorator\NoOpDecorator'),
        );

        $factory = new Factory();
        $factory->setConfiguration($config);

        $registry = $factory->getRegistry($name, 'testSection', new Assertion());

        $this->assertEquals($expected, $registry);
    }
    public function registryNameProvider()
    {
        $section = 'testSection';
        $assertion = new Assertion();
        $decorator = new NoOpDecorator();

        return array(
            'Popo' => array(new Popo($section, $assertion), 'Popo'),
            'D7Config'=> array(new D7Config($section, $assertion), 'D7Config'),
            'mySql' =>array(
                new MySql($section, $assertion, new \PDO('mysql:host=localhost'), $decorator),
                'MySql'
            ),
            'elasticsearch' => array(new Elasticsearch($section, $assertion, $decorator), 'Elasticsearch'),
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Factory::getRegistry
     * @covers \Liip\Drupal\Modules\Registry\Factory::getInstanceOf
     */
    public function testGetInstanceOfExpectingException()
    {
        $factory = new Factory();

        $this->setExpectedException('\Assert\InvalidArgumentException');
        $factory->getRegistry('Name of a non existing registry', 'testSection', new Assertion());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Factory::getConfiguration
     * @covers \Liip\Drupal\Modules\Registry\Factory::setConfiguration
     */
    public function testConfiguration()
    {
        $factory = new Factory();

        $this->assertEmpty($factory->getConfiguration());

        $factory->setConfiguration(array('database' => array('dsn' => 'mysql:host=localhost')));

        $this->assertEquals(
            array('database' => array('dsn' => 'mysql:host=localhost')),
            $factory->getConfiguration()
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Factory::setDecorator
     * @covers \Liip\Drupal\Modules\Registry\Factory::getDecorator
     */
    public function testDecorator()
    {
        $decorator = $this->getMockBuilder('Liip\Registry\Adaptor\Decorator\DecoratorInterface')
            ->getMockForAbstractClass();

        $factory = new Factory();

        $this->assertInstanceOf('\Liip\Registry\Adaptor\Decorator\NoOpDecorator', $factory->getDecorator());

        $factory->setDecorator($decorator);
        $this->assertInstanceOf('\Liip\Registry\Adaptor\Decorator\DecoratorInterface', $factory->getDecorator());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Factory::setDecorator
     * @covers \Liip\Drupal\Modules\Registry\Factory::getDecorator
     */
    public function testDecoratorFromConfiguration()
    {
        $factory = new Factory();
        $configuration = array('name' => '\Liip\Registry\Adaptor\Decorator\NoOpDecorator');

        $factory->setConfiguration($configuration);

        $this->assertInstanceOf(
            '\Liip\Registry\Adaptor\Decorator\NoOpDecorator',
            $factory->getDecorator($configuration)
        );
    }

    /**
     * @dataProvider dbConfigurationProvider
     * @covers \Liip\Drupal\Modules\Registry\Factory::setPdo
     * @covers \Liip\Drupal\Modules\Registry\Factory::getPdo
     */
    public function testPdo($config)
    {
        $factory = new Factory();

        $this->assertInstanceOf('\PDO', $factory->getPdo($config));

        $pdo = $this->getMockBuilder('\PDO')
            ->setConstructorArgs($config)
            ->getMock();

        $factory->setPdo($pdo);
        $this->assertSame($pdo, $factory->getPdo($config));
    }
    public function dbConfigurationProvider()
    {
        return array(
//            no database with a password provided.
//            'complete config' => array(array(
//                'dsn' => 'mysql:host=localhost',
//                'user' => 'foo',
//                'password' => 'foo',
//            )),
            'no password config' => array(array(
                'dsn' => 'mysql:host=localhost',
                'user' => 'foo',
            )),
            'dsn only config' => array(array(
                'dsn' => 'mysql:host=localhost'
            )),
        );
    }
}
