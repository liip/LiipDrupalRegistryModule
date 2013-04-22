<?php
namespace Liip\Drupal\Modules\Registry\Lucene;

use Assert\Assertion;
use Elastica\Client;
use Elastica\Index;
use Liip\Drupal\Modules\DrupalConnector\Common;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class ElasticsearchTest extends RegistryTestCase
{
    /**
     * @var string Name of the es index to be used throughout the test suite.
     */
    protected static $indexName = 'testdocuments';


    protected function setUp()
    {
        if (!class_exists('\Elastica\Index')) {
            $this->markTestSkipped(
                'The elastica library is not available. Please make sure to install the elastica library as proposed by composer.'
            );
        }
    }

    /**
     * restores the state of the elasticsearch cluster before the test suite run.
     */
    public static function tearDownAfterClass()
    {
        $client =  new Client();
        $index = new Index($client, self::$indexName);

        if ($index->exists()) {

            $response = $index->delete();

            if ($response->hasError()) {
                //$this->fail('Failed to tear down the test suite.');

                print_r($response->getError());
            }
        }
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::validateElasticaDependency
     */
    public function testValidateElasticDependency()
    {
        $registry = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Lucene\Elasticsearch')
            ->disableOriginalConstructor()
            ->setMethods(array('validateElasticaDependency'))
            ->getProxy();

        $this->assertNull($registry->validateElasticaDependency());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::init
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::__construct
     */
    public function testInit()
    {
        $registry = $this->getRegistryObject(self::$indexName);

        $this->assertAttributeEquals(self::$indexName, 'section', $registry);
        $this->assertInstanceOf(
            '\Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor',
            $this->readAttribute($registry, 'adaptor')
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::init
     */
    public function testInitExpectingException()
    {
        $registry = $this->getRegistryObject(self::$indexName);

        $this->setExpectedException('\\Liip\\Drupal\\Modules\\Registry\\RegistryException');

        $registry->init();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testRegister()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toRegister', array('automotive' => 'train'));

        $attribRegistry = $this->readAttribute($registry, 'registry');
        $type = $attribRegistry[self::$indexName]->getType('collab');

        $this->assertEquals(
            array('automotive' => 'train'),
            $type->getDocument('toRegister')->getData()
        );
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testRegisterExpecttingException()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toRegister', array('automotive' => 'train'));
        $registry->register('toRegister', array('automotive' => 'train'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::replace
     */
    public function testReplace()
    {
        $registry =  $this->registerDocument(self::$indexName, 'ToReplace', array('devil' => 'Debian'));
        $registry->replace('ToReplace', array('devil' => 'Tux'));

        $content = $registry->getContent();
        $index = $content[self::$indexName];
        $type = $index->getType('collab');

        $this->assertEquals(
            array('devil' => 'Tux'),
            $type->getDocument('ToReplace')->getData()
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::replace
     */
    public function testReplaceExpectingRegistgryException()
    {
        $registry =  $this->registerDocument(self::$indexName, 'JohnDoe', array('devil' => 'Debian'));

        $this->setExpectedException('\\Liip\\Drupal\\Modules\\Registry\\RegistryException');

        $registry->replace('documentDoesNotExist', array('this' => 'must not be empty'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::unregister
     */
    public function testUnregister()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toUnregister', array('devil' => 'Debian'));
        $registry->unregister('toUnregister');

        $content = $registry->getContent();
        $index = $content[self::$indexName];
        $type = $index->getType('collab');

        $this->setExpectedException('\\Elastica\\Exception\\NotFoundException');
        $doc = $type->getDocument('toUnregister');
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::unregister
     */
    public function testUnregisterExpectingException()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toUnregister', array('devil' => 'Debian'));
        $registry->unregister('notExistingDocument');

    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::destroy
     */
    public function testDestroy()
    {
        $registry = $this->getRegistryObject(self::$indexName);
        $registry->destroy();

        $this->assertAttributeEmpty('registry', $registry);
        $this->assertEmpty($registry->getContent());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::isRegistered
     */
    public function testIsRegistered()
    {
        $registry =  $this->registerDocument(self::$indexName, 'isRegistered', array('devil' => 'Debian'));

        $this->assertTrue($registry->isRegistered('isRegistered'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::isRegistered
     */
    public function testIsNotRegistered()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toGoodToBeTrue', array('tux' => 'linus'));

        $this->assertTrue($registry->isRegistered('toGoodToBeTrue'));
        $this->assertFalse($registry->isRegistered('isNotRegistered'));
    }
}
