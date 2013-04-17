<?php

namespace Liip\Drupal\Modules\Registry\Adaptor;

use Assert\Assertion;
use Elastica\Client;
use Elastica\Index;

class ElasticaAdaptorFunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string Name of the es index to be used throughout the test suite.
     */
    protected $indexName = 'testindex';


    public static function tearDownAfterClas()
    {
        $client =  new Client();
        $index = new Index($client, $this->indexName);
        $response = $index->delete();

        if ($response->hasError()) {
            $this->fail('Failed to tear down the test suite.');
        }
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getIndex
     */
    public function testGetIndex()
    {
        $adaptor = new ElasticaAdaptor();
        $index = $adaptor->getIndex($this->indexName);

        $attrib = $this->readAttribute($adaptor, 'indexes');

        $this->assertSame($index, $attrib[$this->indexName]);
        $this->assertInstanceOf('\Elastica\Index', $index);
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::getIndex
     */
    public function testGetIndexFromCache()
    {
    }

    /**
     * @dataProvider registerDocumentDataprovider
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor::registerDocument
     */
    public function testRegisterDocumentExpectingException($value)
    {
        $this->setExpectedException('\Assert\InvalidArgumentException');

        $adaptor = new ElasticaAdaptor();
        $adaptor->registerDocument('Foo', $value);
    }

    public static function registerDocumentDataprovider()
    {
        return array(
            'invalid data format' => array('valid_id', array()),
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

        $this->assertInstanceOf('\Elastica\Document', $adaptor->registerDocument($this->indexName, $value));
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
}
