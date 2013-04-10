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
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::validateOptions
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
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::validateElasticaDependency
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

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::validateOptions
     */
    public function testInvalidOptions()
    {
        $this->setExpectedException('\Liip\Drupal\Modules\Registry\RegistryException');

        $registry = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Lucene\Elasticsearch')
            ->disableOriginalConstructor()
            ->setMethods(array('validateOptions'))
            ->getProxy();

        $registry->validateOptions(array());
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Lucene\Elasticsearch::validateOptions
     */
    public function testValidateOptions()
    {
        $registry = $this->getProxyBuilder('\Liip\Drupal\Modules\Registry\Lucene\Elasticsearch')
            ->disableOriginalConstructor()
            ->setMethods(array('validateOptions'))
            ->getProxy();

        $this->assertNull($registry->validateOptions($this->getElasticsearchOptions()));
    }
}
