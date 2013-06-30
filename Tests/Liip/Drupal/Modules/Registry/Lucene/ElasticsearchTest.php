<?php
namespace Liip\Drupal\Modules\Registry\Lucene;

use Elastica\Client;
use Elastica\Index;
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
    public function tearDown()
    {
        $client =  new Client();
        $index = new Index($client, self::$indexName);

        if ($index->exists()) {

            $response = $index->delete();

            if ($response->hasError()) {

                throw new \PHPUnit_Framework_Exception(
                    sprintf(
                        'Failed to delete the elasticsearch index: %s',
                        self::$indexName
                    )
                );
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
        $registry->destroy();
        $registry->init();

        $this->assertAttributeEquals(self::$indexName, 'section', $registry);
        $this->assertInstanceOf(
            '\Liip\Drupal\Modules\Registry\Adaptor\Lucene\ElasticaAdaptor',
            $this->readAttribute($registry, 'adaptor')
        );
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
            array('array'=> '{"automotive":"train"}'),
            $type->getDocument('toRegister')->getData()
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testRegisterWithType()
    {
        $typeName = 'customTypeName';
        $registry =  $this->registerDocument(self::$indexName, 'toRegister2', array('foo' => 'bar'), $typeName);

        $attribRegistry = $this->readAttribute($registry, 'registry');
        $type = $attribRegistry[self::$indexName]->getType($typeName);

        $this->assertEquals(
            array('array'=> '{"foo":"bar"}'),
            $type->getDocument('toRegister2')->getData()
        );
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testRegisterExpectingException()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toRegister', array('automotive' => 'train'));
        $registry->register('toRegister', array('automotive' => 'train'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::replace
     */
    public function testReplace()
    {
        $registry = $this->registerDocument(self::$indexName, 'ToReplace', array('devil' => 'Debian'));
        $registry->replace('ToReplace', array('devil' => 'Tux'));

        $this->assertEquals(
            array('devil' => 'Tux'),
            $registry->getContentById('ToReplace')
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testReplaceWithType()
    {
        $typeName = 'newTypeName';
        $identifier = 'toReplaceWithType';

        $registry = $this->registerDocument(self::$indexName, $identifier, array('devil' => 'old'), $typeName);
        $registry->replace($identifier, array('devil' => 'new'), $typeName);

        $this->assertEquals(
            array('devil' => 'new'),
            $registry->getContentById($identifier, '', $typeName)
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

        $this->setExpectedException('\\Elastica\\Exception\\NotFoundException');
        $content = $registry->getContentById('toUnregister');
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

        $client = new Client();
        $index = $client->getIndex(self::$indexName);

        $this->assertFalse($index->exists());
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

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getContent
     */
    public function testGetContent()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toReadContent', array('tux' => 'linus'));

        $this->assertEquals(
            array(
                'toReadContent'  => array("tux" => "linus"),
            ),
            $registry->getContent());
    }

    /**
     * @dataProvider getContentByIdDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getContentById
     */
    public function testGetContentById($expected, $value)
    {
        $registry =  $this->registerDocument(self::$indexName, 'toReadContentByIdFrom', $value);

        $this->assertEquals($expected, $registry->getContentById('toReadContentByIdFrom'));
    }
    public static function getContentByIdDataprovider()
    {
        return array(
            'store assoc array' => array(
                array('tux' => 'linus'),
                array('tux' => 'linus')
            ),
            'store numbered array' => array(
                array('tux', 'linus'),
                array('tux', 'linus')
            ),
            'store object' => array(
                (object) array('tux' => 'linus'),
                (object) array('tux' => 'linus')
            ),
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getContentByIds
     */
    public function testGetContentByIds()
    {
        $registry =  $this->registerDocument(self::$indexName, 'toReadContentByIds', array('tux' => 'linux'));
        $registry->register('toReadContentByIds1', array('Foo' => 'bar'));
        $registry->register('toReadContentByIds2', array('John' => 'Doe'));

        $this->assertEquals(
            array(
                'toReadContentByIds' => array('tux' => 'linux'),
                'toReadContentByIds2' => array('John' => 'Doe')
            ),
            $registry->getContentByIds(
                array(
                    'toReadContentByIds',
                    'toReadContentByIds2'
                )
            )
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getESAdaptor
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::setESAdaptor
     */
    public function testGetEsAdaptor()
    {
        $esAdaptorFake = $this->getMockBuilder('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\Lucene\\AdaptorInterface')
            ->getMockForAbstractClass();

        $registry = $this->getRegistryObject(self::$indexName);
        $registry->setESAdaptor($esAdaptorFake);

        $this->assertSame($esAdaptorFake, $registry->getESAdaptor());

    }
}
