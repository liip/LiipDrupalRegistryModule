<?php

namespace Liip\Drupal\Modules\Registry\Adaptor;

use Assert\Assertion;
use Elastica\Client;
use Elastica\Index;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class ElasticaAdaptorFunctionalTest extends RegistryTestCase
{
    /**
     * @var string Name of the es index to be used throughout the test suite.
     */
    protected static $indexName = 'testindex';


    public static function tearDownAfterClass()
    {
        $client =  new Client();
        $index = new Index($client, self::$indexName);
        $response = $index->delete();

        if ($response->hasError()) {
            //$this->fail('Failed to tear down the test suite.');

            print_r($response->getError());
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
     * @dataProvider registerDocumentDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocumentExpectingException($value)
    {
        $this->setExpectedException('\Assert\InvalidArgumentException');

        $adaptor = new ElasticaAdaptor();
        $adaptor->registerDocument(self::$indexName, $value, 'myDocument');
    }
    public static function registerDocumentDataprovider()
    {
        return array(
            'invalid data format (empty array)' => array('valid_id', array()),
            'invalid data format (no array)' => array('valid_id', 'Tux'),
            'invalid identifier' => array('invalidIdentifier', array('Tux' => 'bar'))
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocument()
    {
        $value = array('Mascott' => 'Tux');

        $adaptor = new ElasticaAdaptor();

        $this->assertInstanceOf('\Elastica\Document', $adaptor->registerDocument(self::$indexName, $value));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::updateDocument
     */
    public function testUpdateDocument()
    {
        $adaptor = new ElasticaAdaptor();
        $document = $adaptor->registerDocument(
            self::$indexName,
            array('food' => 'Moules'),
            'foodStock'
        );

        $rawData = array(
            'doc' => array(
                'food' => 'Crisps',
                'nearNonFood' => 'Sponch'
            )
        );

        $updatedDocument = $adaptor->updateDocument(
            'foodStock',
            $rawData,
            self::$indexName
        );

        $data = $document->getData();

        $this->assertArrayHasKey('nearNonFood', $data);
        $this->assertEquals('Sponch', $data['nearNonFood']);
        $this->assertArrayHasKey('food', $data);
        $this->assertEquals('Crisps', $data['food']);
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

        $client = $this->getMockBuilder('\\Elastica\CLient')
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

        $updatedDocument = $adaptor->updateDocument(
            'foodStock',
            $rawData,
            self::$indexName
        );
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getClient
     */
    public function testGetClient()
    {
        $registry = new ElasticaAdaptor();
        $client = $registry->getClient();

        $this->assertAttributeInstanceOf('\Elastica\Client', 'client', $registry);
        $this->assertInstanceOf('\Elastica\Client', $client);
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

}
