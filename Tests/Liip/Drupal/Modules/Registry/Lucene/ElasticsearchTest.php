<?php
namespace Liip\Drupal\Modules\Registry\Lucene;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class ElasticsearchTest extends RegistryTestCase
{
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
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::destroy
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::__construct
     */
    public function testInvalidateElasticaDependency()
    {
        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');

        $registry = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Lucene\Elasticsearch')
            ->disableOriginalConstructor()
            ->setMethods(array('validateElasticaDependency'))
            ->getProxy();

        $registry->validateElasticaDependency();
    }
}
