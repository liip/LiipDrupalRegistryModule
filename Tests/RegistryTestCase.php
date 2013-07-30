<?php
namespace Liip\Drupal\Modules\Registry\Tests;

use Assert\Assertion;
use Liip\Registry\Adaptor\Decorator\NormalizeDecorator;
use Liip\Registry\Adaptor\Lucene\ElasticaAdaptor;
use Liip\Drupal\Modules\Registry\Lucene\Elasticsearch;
use lapistano\ProxyObject\ProxyBuilder;

abstract class RegistryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Provides a ElasticaAdaptor with the normalizer decorator
     *
     * @return ElasticaAdaptor
     */
    protected function getElasticaAdapter()
    {
        return new ElasticaAdaptor(new NormalizeDecorator());
    }

    /**
     * Provides an instance of the ProxyBuilder
     *
     * @param string $className
     *
     * @return \lapistano\ProxyObject\ProxyBuilder
     */
    protected function getProxyBuilder($className)
    {
        return new ProxyBuilder($className);
    }

    /**
     * Provides a stub of the \Assert\Assertion class;
     *
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAssertionObjectMock(array $methods = array())
    {
        return $this->getMockBuilder('\\Assert\\Assertion')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Provides a registry with a registered document.
     *
     * @param string $indexName
     * @param string $documentId
     * @param mixed $data
     * @param string $typeName
     *
     * @return Elasticsearch
     */
    protected function registerDocument($indexName, $documentId, $data, $typeName = '')
    {
        $registry = $this->getRegistryObject($indexName);
        $registry->register($documentId, $data, $typeName);

        return $registry;
    }
}
