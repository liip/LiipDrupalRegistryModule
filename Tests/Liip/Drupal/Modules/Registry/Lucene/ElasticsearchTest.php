<?php
namespace Liip\Drupal\Modules\Registry\Lucene;

use Assert\Assertion;
use Elastica\Client;
use Elastica\Exception\ClientException;
use Elastica\Index;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;
use Liip\Registry\Adaptor\Decorator\NormalizeDecorator;

class ElasticsearchTest extends RegistryTestCase
{
    /**
     * @var string Name of the es index to be used throughout the test suite.
     */
    protected static $indexName = 'testdocuments';

    public static function getContentByIdDataprovider()
    {
        return array(
            'store assoc array'    => array(
                array('tux' => 'linus'),
                array('tux' => 'linus')
            ),
            'store numbered array' => array(
                array('tux', 'linus'),
                array('tux', 'linus')
            ),
        );
    }

    /**
     * restores the state of the elasticsearch cluster before the test suite run.
     */
    public function tearDown()
    {
        $client = new Client();
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
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getRegistryIndex
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::__construct
     */
    public function testInit()
    {
        $registry = $this->getRegistryObject(self::$indexName);
        $registry->destroy();
        $registry->init();

        $this->assertAttributeEquals(self::$indexName, 'section', $registry);
        $this->assertInstanceOf(
            '\Liip\Registry\Adaptor\Lucene\ElasticaAdaptor',
            $this->readAttribute($registry, 'adaptor')
        );
    }

    /**
     * Provides an instance of the Elasticsearch object.
     *
     * @param $indexName
     *
     * @return Elasticsearch
     */
    protected function getRegistryObject($indexName)
    {
        $registry = new Elasticsearch(
            $indexName,
            new Assertion(),
            new NormalizeDecorator()
        );

        return $registry;
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testRegister()
    {
        $registry = $this->registerDocument(self::$indexName, 'toRegister', array('automotive' => 'train'));
        $registry->init();

        $attribRegistry = $this->readAttribute($registry, 'registry');
        $type = $attribRegistry[self::$indexName]->getType('collab');

        $this->assertEquals(
            array('array' => '{"automotive":"train"}'),
            $type->getDocument('toRegister')->getData()
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testRegisterWithType()
    {
        $typeName = 'customTypeName';
        $registry = $this->registerDocument(self::$indexName, 'toRegister2', array('foo' => 'bar'), $typeName);
        $registry->init();

        $attribRegistry = $this->readAttribute($registry, 'registry');
        $type = $attribRegistry[self::$indexName]->getType($typeName);

        $this->assertEquals(
            array('array' => '{"foo":"bar"}'),
            $type->getDocument('toRegister2')->getData()
        );
    }

    /**
     * @expectedException \Liip\Drupal\Modules\Registry\RegistryException
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::register
     */
    public function testRegisterExpectingException()
    {
        $registry = $this->registerDocument(self::$indexName, 'toRegister', array('automotive' => 'train'));
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
        $registry = $this->registerDocument(self::$indexName, 'JohnDoe', array('devil' => 'Debian'));

        $this->setExpectedException('\\Liip\\Drupal\\Modules\\Registry\\RegistryException');

        $registry->replace('documentDoesNotExist', array('this' => 'must not be empty'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::unregister
     */
    public function testUnregister()
    {
        $registry = $this->registerDocument(self::$indexName, 'toUnregister', array('devil' => 'Debian'));
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
        $registry = $this->registerDocument(self::$indexName, 'toUnregister', array('devil' => 'Debian'));
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
        $registry = $this->registerDocument(self::$indexName, 'isRegistered', array('devil' => 'Debian'));

        $this->assertTrue($registry->isRegistered('isRegistered'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::isRegistered
     */
    public function testIsNotRegistered()
    {
        $registry = $this->registerDocument(self::$indexName, 'toGoodToBeTrue', array('tux' => 'linus'));

        $this->assertTrue($registry->isRegistered('toGoodToBeTrue'));
        $this->assertFalse($registry->isRegistered('isNotRegistered'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getContent
     */
    public function testGetContent()
    {
        $registry = $this->registerDocument(self::$indexName, 'toReadContent', array('tux' => 'linus'));

        $this->assertEquals(
            array(
                'toReadContent' => array("tux" => "linus"),
            ),
            $registry->getContent());
    }

    /**
     * @dataProvider getContentByIdDataprovider
     * @covers       \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getContentById
     */
    public function testGetContentById($expected, $value)
    {
        $registry = $this->registerDocument(self::$indexName, 'toReadContentByIdFrom', $value);

        $this->assertEquals($expected, $registry->getContentById('toReadContentByIdFrom'));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getContentByIds
     */
    public function testGetContentByIds()
    {
        $registry = $this->registerDocument(self::$indexName, 'toReadContentByIds', array('tux' => 'linux'));
        $registry->register('toReadContentByIds1', array('Foo' => 'bar'));
        $registry->register('toReadContentByIds2', array('John' => 'Doe'));

        $this->assertEquals(
            array(
                'toReadContentByIds'  => array('tux' => 'linux'),
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
    public function testGetEsAdaptorFromCache()
    {
        $esAdaptorFake = $this->getMockBuilder('\\Liip\\Registry\\Adaptor\\Lucene\\AdaptorInterface')
            ->getMockForAbstractClass();

        $registry = $this->getRegistryObject(self::$indexName);
        $registry->setESAdaptor($esAdaptorFake);

        $this->assertSame($esAdaptorFake, $registry->getESAdaptor());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getESAdaptor
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::setESAdaptor
     */
    public function testGetEsAdaptor()
    {
        $registry = $this->getRegistryObject(self::$indexName);

        $this->assertInstanceOf('\Liip\Registry\Adaptor\Lucene\ElasticaAdaptor', $registry->getESAdaptor());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::__construct
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getESAdaptor
     */
    public function testGetEsAdaptorExceptionExpected()
    {
        $registry = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Lucene\\Elasticsearch')
            ->disableOriginalConstructor()
            ->setProperties(array('assertion'))
            ->getProxy();
        $registry->assertion = new Assertion();

        $this->setExpectedException('\\Assert\\InvalidArgumentException');
        $registry->getEsAdaptor();
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getIndexOptions
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::setIndexOptions
     */
    public function testIndexOptions()
    {
        $options = array(
            'number_of_shards'   => 4,
            'number_of_replicas' => 1,
            'analysis'           => array(
                'analyzer' => array(
                    'indexAnalyzer'  => array(
                        'type'      => 'custom',
                        'tokenizer' => 'standard',
                        'filter'    => array('lowercase', 'mySnowball')
                    ),
                    'searchAnalyzer' => array(
                        'type'      => 'custom',
                        'tokenizer' => 'standard',
                        'filter'    => array('standard', 'lowercase', 'mySnowball')
                    )
                ),
                'filter'   => array(
                    'mySnowball' => array(
                        'type'     => 'snowball',
                        'language' => 'German'
                    )
                )
            )
        );

        $registry = $this->getRegistryObject(self::$indexName);
        $registry->setIndexOptions($options);

        $this->assertEquals($options, $registry->getIndexOptions());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getIndexSpecials
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::setIndexSpecials
     */
    public function testIndexSpecials()
    {
        $options = array(
            'recreate' => true,
            'routing' => 'r1,r2'
        );

        $registry = $this->getRegistryObject(self::$indexName);
        $registry->setIndexSpecials($options);

        $this->assertEquals($options, $registry->getIndexSpecials());
    }

    protected function setUp()
    {
        // ElasticServer up?
        $alive = @file_get_contents('http://localhost:9200');

        if (is_bool($alive) && !$alive) {
            $connectionRefused = true;
        } else {

            $response = json_decode($alive);
            $connectionRefused = !(200 === $response->status);
        }

        if ($connectionRefused) {
            $this->markTestSkipped(
                'The ElasticSearch server is not responding. Please make sure to start the server before running the tests.'
            );
        }


        if (!class_exists('\Elastica\Index')) {
            $this->markTestSkipped(
                'The elastica library is not available. Please make sure to install the elastica library as proposed by composer.'
            );
        }

        try {
            $adaptor = $this->getElasticaAdapter();
            $adaptor->getIndex(self::$indexName);

        } catch (ClientException $e) {
            $this->markTestSkipped(
                'The connection attempt to elasticsearch server failed. Error: ' . $e->getMessage()
            );
        }
    }

    /**
     * @dataProvider limitSettingsProvider
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getContent
     */
    public function testGetAmountOfDocuments($expected, $limit)
    {
        $values = array(
            array('tux' => 'linus'),
            array('tux1' => 'dolphin1'),
            array('tux2' => 'linus1'),
            array('tux3' => 'linus2'),
            array('tux4' => 'dolphin2'),
            array('tux5' => 'linus3'),
            array('tux6' => 'dolphin3'),
            array('Gnu' => 'dolphin'),
            array('Gnu1' => 'dolphin5'),
            array('Gnu2' => 'dolphin4'),
            array('Gnu3' => 'dolphin6'),
        );

        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom11', $values[0]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom1', $values[1]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom2', $values[2]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom3', $values[3]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom4', $values[4]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom5', $values[5]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom6', $values[5]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom7', $values[6]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom8', $values[7]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom9', $values[8]);
        $this->registerDocument(self::$indexName, 'toReadContentByIdFrom10', $values[9]);
        $registry = $this->registerDocument(self::$indexName, 'toReadContentByIdFrom', $values[10]);

        $this->assertCount($expected, $registry->getContent($limit));
    }

    public function limitSettingsProvider()
    {
        return array(
            'just one document' => array(1, 1),
            'all documents in index' => array(12, 0),
            'default amount of documents' => array(10, 10),
            'five documents' => array(5, 5),
        );
    }
}
