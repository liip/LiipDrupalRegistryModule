<?php

namespace Liip\Drupal\Modules\Registry\Drupal\Database;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Database\MySql;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;


class MySqlTest extends RegistryTestCase
{
    protected function getDbConfig()
    {
        return array(
            'dsn' => 'mysql:host=localhost;dbname=registry',
            'user' => '',
            'password' => '',
            'database' => 'registry',
            'tableSQL' => "CREATE TABLE IF NOT EXISTS `%s` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `entityId` int(11) NOT NULL,
                `created` datetime DEFAULT NULL,
                `data` text COLLATE utf8_unicode_ci NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `UNIQ_E275B389261FB672` (`entityId`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci",
        );
    }

    public function setUp()
    {
        $config = $this->getDbConfig();

        try {

            if (!empty($config['password'])) {

                $mysql = new \PDO($config['dsn'], $config['user'], $config['password']);
            } else {

                $mysql = new \PDO($config['dsn'], $config['user']);
            }
        } catch (\PDOException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::setConfiguration
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::validateConfiguration
     */
    public function testSetConfiguration()
    {
        $config = $this->getDbConfig();

        $assertion = $this->getAssertionObjectMock();
        $registry = new MySql('MyRegistry', $assertion);

        $registry->setConfiguration($config);

        $this->assertAttributeEquals($config, 'dbConfig', $registry);
    }

    /**
     * @dataProvider mySqlConfigurationProvider
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::validateConfiguration
     */
    public function testValidateConfigurationExpectingException($config)
    {
        $registry = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Database\MySql')
            ->setConstructorArgs(array('Myregistry', new Assertion()))
            ->setMethods(array('validateConfiguration'))
            ->getProxy();

        $this->setExpectedException('\Assert\InvalidArgumentException');

        $registry->validateConfiguration($config);
    }
    public function mySqlConfigurationProvider()
    {
        return array(
            'missing »tableSQL«' => array(array('user' => 'tux', 'dsn' => 'mysql:host=localhost')),
            'missing »dsn«' => array(array('user' => 'tux', 'tableSQL' => 'CREATE %S [..]')),
            'missing »user«' => array(array('dsn' => 'mysql:host=localhost', 'tableSQL' => 'CREATE %S [..]')),
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Database\MySql::init
     */
    public function testInit()
    {
        $expected = array('myregistry' => array());
        $assertion = $this->getAssertionObjectMock();
        $registry = new MySql('MyRegistry', $assertion);

        $registry->setConfiguration($this->getDbConfig());

        $registry->init();

        $this->assertAttributeEquals($expected, 'registry', $registry);
    }
}
