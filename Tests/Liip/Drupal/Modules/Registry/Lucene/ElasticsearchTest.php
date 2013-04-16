<?php
namespace Liip\Drupal\Modules\Registry\Lucene;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class ElasticsearchTest extends RegistryTestCase
{
    protected function setUp()
    {
        if (!class_exists('\Elastica\Index')) {
            $this->markTestSkipped(
                'The elastica library is not available. Please make sure to install the elastica library as proposed by composer.'
            );
        }
    }

    /**
     * Provides an array reflecting the configuration options of Elasticsearch.
     *
     * Supported elasticsearch version: 0.20
     *
     * @return array
     */
    protected function getElasticsearchOptions()
    {
        return array(
            'network' => array(
                'host' => '0.0.0.0',
            ),
            'path' => array(
                'logs' => '/var/log/elasticsearch',
                'data' => '/var/data/elasticsearch',
            ),
            'cluster' => array(
                'name' => '<NAME OF YOUR CLUSTER>',
            ),
            'node' => array(
                'name' => '<NAME OF YOUR NODE>'
            ),
            'index' => array(
                'store' => array(
                    'type' => 'memory'
                ),
            ),
        );
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
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::getElasticaClient
     */
    public function testgetElasticaClient()
    {
        $registry = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Lucene\Elasticsearch')
            ->disableOriginalConstructor()
            ->setMethods(array('getElasticaClient'))
            ->getProxy();

        $client = $registry->getElasticaClient();

        $this->assertAttributeInstanceOf('\Elastica\Client', 'elasticaClient', $registry);
        $this->assertInstanceOf('\Elastica\Client', $client);
    }

    public function testGetElasticaIndex()
    {
        $registry = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Lucene\Elasticsearch')
            ->disableOriginalConstructor()
            ->setMethods(array('getElasticaIndex'))
            ->getProxy();

        $index = $registry->getElasticaIndex('Tux');

        $attrib = $this->readAttribute($registry, 'indexes');
        $this->assertInstanceOf('\Elastica\Index', $attrib['Tux']);

        $this->assertSame($index, $attrib['Tux']);
    }
}
