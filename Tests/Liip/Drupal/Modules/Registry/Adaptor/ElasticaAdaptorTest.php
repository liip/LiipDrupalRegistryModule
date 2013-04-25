<?php

namespace Liip\Drupal\Modules\Registry\Adaptor;

use Assert\Assertion;
use Elastica\Client;
use Elastica\Index;
use Elastica\Result;
use Liip\Drupal\Modules\DrupalConnector\Common;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class ElasticaAdaptorFunctionalTest extends RegistryTestCase
{
    /**
     * @var string Name of the es index to be used throughout the test suite.
     */
    protected static $indexName = 'testindex';

    /**
     * Determines if elasticsearch is installed. It makes no sense to run this tests if not.
     */
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
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getIndex
     */
    public function testGetIndex()
    {
        $adaptor = new ElasticaAdaptor();
        $index = $adaptor->getIndex(self::$indexName);

        $attrib = $this->readAttribute($adaptor, 'indexes');

        $this->assertSame($index, $attrib[self::$indexName]);
        $this->assertInstanceOf('\Elastica\Index', $index);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getIndex
     */
    public function testGetIndexFromCache()
    {
        $adaptor = new ElasticaAdaptor();
        $index = $adaptor->getIndex(self::$indexName);

        $this->assertSame($index, $adaptor->getIndex(self::$indexName));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocumentExpectingException()
    {
        $this->setExpectedException('\Assert\InvalidArgumentException');

        $adaptor = new ElasticaAdaptor();
        $adaptor->registerDocument(self::$indexName, array(), 'myDocument');
    }

    /**
     * @dataProvider registerDocumentDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocument($value)
    {
        $adaptor = new ElasticaAdaptor();

        $this->assertInstanceOf(
            '\Elastica\Document',
            $adaptor->registerDocument(self::$indexName, $value)
        );
    }
    public static function registerDocumentDataprovider()
    {
        return array(
            'valid array data'   => array(array('Mascott' => 'Tux')),
            'valid string data'  => array('Tux'),
            'valid integer data' => array(1),
            'valid double data'  => array(1.1)
        );
    }

    /**
     * @dataProvider updateDocumentDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::updateDocument
     */
    public function testUpdateDocument($expected, $id, $registerData, $updateData)
    {
        $adaptor = new ElasticaAdaptor();
        $adaptor->registerDocument(
            self::$indexName,
            $registerData,
            $id
        );

        $updatedDocument = $adaptor->updateDocument(
            $id,
            $updateData,
            self::$indexName
        );

        $data = $updatedDocument->getData();

        $this->assertEquals($expected, $data);
    }
    public static function updateDocumentDataprovider()
    {
        return array(
            'valid array data'   => array(
                array(
                    'food' => 'Crisps',
                    'nearNonFood' => 'Sponch',
                    'Mascott' => 'Tux'
                ),
                'foodStock',
                array('Mascott' => 'Tux'),
                array(
                    'food' => 'Crisps',
                    'nearNonFood' => 'Sponch'
                ),
            ),
            'valid string data'  => array(
                array(
                    'string' => 'OUYA'
                ),
                'gamingConsoles',
                'XBOX',
                'OUYA',
            ),
            'valid integer data' => array(
                array(
                    'integer' => 9911
                ),
                "numbers",
                1,
                9911
            ),
            'valid double data'  => array(
                array(
                    'double' => 10.040401
                ),
                "doubles",
                1.1,
                10.040401
            )
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::updateDocument
     */
    public function testUpdateDocumentExpectingException()
    {
        $response = $this->getMockBuilder('\\Elastica\\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('hasError', 'getError'))
            ->getMock();
        $response
            ->expects($this->once())
            ->method('hasError')
            ->will($this->returnValue(true));

        $client = $this->getMockBuilder('\\Elastica\\Client')
            ->setMethods(array('updateDocument'))
            ->getMock();
        $client
            ->expects($this->once())
            ->method('updateDocument')
            ->will($this->returnValue($response));

        $index = $this->getMockBuilder('\\Elastica\Index')
            ->disableOriginalConstructor()
            ->setMethods(array('getClient'))
            ->getMock();
        $index
            ->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $adaptor = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptor')
            ->setProperties(array('indexes'))
            ->getProxy();

        $adaptor->indexes[self::$indexName] = $index;

        $this->setExpectedException('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptorException');

        $rawData = array(
            'doc' => array(
                'food' => 'Crisps',
                'nearNonFood' => 'Sponch'
            )
        );

        $adaptor->updateDocument(
            'foodStock',
            $rawData,
            self::$indexName
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getDocument
     */
    public function testGetDocument()
    {
        $adaptor = new ElasticaAdaptor();
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRetrieved'
        );

        $this->assertEquals(
            array('tux' => 'devil'),
            $adaptor->getDocument('toBeRetrieved',
                self::$indexName)
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getDocuments
     */
    public function testGetDocuments()
    {
        $adaptor = new ElasticaAdaptor();
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRetrieved'
        );
        $adaptor->registerDocument(
            self::$indexName,
            array('mascott' => 'Gnu'),
            'toBeRetrieved2'
        );

        $this->assertEquals(
            array(
                'toBeRetrieved' => array('tux' => 'devil'),
                'toBeRetrieved2' => array('mascott' => 'Gnu'),
            ),
            $adaptor->getDocuments($adaptor->getIndex(self::$indexName))
        );

    }

    /**
     * @dataProvider normalizeValueDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::normalizeValue
     */
    public function testNormalizeValue($value)
    {
        $adaptor = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptor')
            ->setMethods(array('normalizeValue'))
            ->getProxy();

        $valueArray = $adaptor->normalizeValue($value);
        $key = gettype($value);

        $this->assertInternalType('array', $valueArray);
        $this->assertEquals(1, sizeof($valueArray));
    }
    public static function normalizeValueDataprovider()
    {
        return array(
            'number value' => array(1),
            'float value'  => array(1.1),
            'string value' => array('blob'),
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::normalizeValue
     */
    public function testNormalizeValueWithArray()
    {
        $adaptor = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptor')
            ->setMethods(array('normalizeValue'))
            ->getProxy();

        $array = array('value one', 'value two', 'value three');

        $convertedArray = $adaptor->normalizeValue($array);

        $this->assertSame($array, $convertedArray);
    }

    /**
     * @dataProvider denormalizeArrayDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::denormalizeValue
     */
    public function testDenormalizeArray($expected, $array)
    {
        $adaptor = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptor')
            ->setMethods(array('denormalizeValue'))
            ->getProxy();

        $value = $adaptor->denormalizeValue($array);

        $this->assertEquals($expected, $value);
    }
    public static function denormalizeArrayDataprovider()
    {
        return array(
            'normalized number array' => array(1, array('integer' => 1)),
            'normalized float array'  => array(1.1, array('double'  => 1.1)),
            'normalized string array' => array('blob', array('string'  => 'blob')),
            'usual data' => array(array('tux' => 'mascott'), array('tux' => 'mascott')),
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::denormalizeValue
     */
    public function testDenormalizeArrayWithNonArray()
    {
        $adaptor = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptor')
            ->setMethods(array('denormalizeValue'))
            ->getProxy();

        $notAnArray = 1;

        $value = $adaptor->denormalizeValue($notAnArray);

        $this->assertNotInternalType('array', $value);
        $this->assertSame($notAnArray, $value);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::removeDocuments
     */
    public function testRemoveDocuments()
    {
        $adaptor = new ElasticaAdaptor();
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRemoved'
        );
        $adaptor->registerDocument(
            self::$indexName,
            array('tux' => 'devil'),
            'toBeRemoved2'
        );

        $index = $adaptor->getIndex(self::$indexName);
        $type = $index->getType('collab');

        $adaptor->removeDocuments(array('toBeRemoved', 'toBeRemoved2'), self::$indexName);

        // this is afaik the only safe way to really find out if the document was removed from index.
        $this->setExpectedException('\\Elastica\\Exception\\NotFoundException');
        $type->getDocument('toBeRemoved');
        $type->getDocument('toBeRemoved2');
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getClient
     */
    public function testGetClient()
    {
        $registry = new ElasticaAdaptor();
        $client = $registry->getClient();

        $this->assertAttributeInstanceOf('\\Elastica\\Client', 'client', $registry);
        $this->assertInstanceOf('\\Elastica\\Client', $client);
    }

    /**
     * @dataProvider normalizeErrorDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::normalizeError
     */
    public function testNormalizeError($error)
    {
        $adaptor = new ElasticaAdaptor();

        $this->assertInstanceOf(
            '\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptorException',
            $adaptor->normalizeError($error)
        );
    }
    public static function normalizeErrorDataprovider()
    {
        return array(
            'error is a string' => array('The leprechauns made me do it!!'),
            'error is an array' => array(array('The leprechauns made me do it!!')),
            'error is of type ElasticaAdaptorException' => array(
                new ElasticaAdaptorException('The leprechauns made me do it!!')
            ),
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::deleteIndex
     */
    public function testDeleteIndex()
    {
        $adaptor = new ElasticaAdaptor();
        $adaptor->getIndex(self::$indexName);

        $adaptor->deleteIndex(self::$indexName);

        $client = new Client();
        $index = $client->getIndex(self::$indexName);

        $this->assertFalse($index->exists());
    }

    /**
     * @dataProvider extractDataDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::extractData
     */
    public function testExtractData($expected, $value)
    {
        $registry = $this->getProxyBuilder('\\Liip\\Drupal\\Modules\\Registry\\Adaptor\\ElasticaAdaptor')
            ->setMethods(array('extractData'))
            ->getProxy();

        $this->assertEquals($expected, $registry->extractData($value));
    }
    public static function extractDataDataprovider()
    {
        return array(
            'Data of type array' => array(
                array(
                    'WorldOfOs' => array('mascott' => 'tux'),
                    'GuggiMenu' => array('Dish Of Day' => 'Salmon al limone'),
                ),
                array(
                    0 => new Result(array(
                        '_index' => 'registry_worlds',
                        '_type' => 'collab',
                        '_id' => 'WorldOfOs',
                        '_score' => 1,
                        '_source'=> array('mascott' => 'tux'),
                    )),
                    1 => new Result(array(
                        '_index' => 'registry_worlds',
                        '_type' => 'collab',
                        '_id' => 'GuggiMenu',
                        '_score' => 1,
                        '_source'=> array('Dish Of Day' => 'Salmon al limone'),
                    )),
                )
            ),
            'Data of type string' => array(
                array('WorldOfOs' => 'this is a string'),
                array(
                    0 => new Result(array(
                        '_index' => 'registry_worlds',
                        '_type' => 'collab',
                        '_id' => 'WorldOfOs',
                        '_score' => 1,
                        '_source'=> 'this is a string',
                    )),
                )
            ),
        );
    }
}
