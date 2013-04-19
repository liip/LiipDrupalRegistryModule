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
}
