<?php
namespace Liip\Drupal\Modules\Registry\Tests;

use lapistano\ProxyObject\ProxyBuilder;
use Liip\Drupal\Modules\Registry\Lucene\Elasticsearch;
use Liip\Drupal\Modules\Registry\RegistryInterface;
use Liip\Registry\Adaptor\Decorator\NormalizeDecorator;
use Liip\Registry\Adaptor\Lucene\ElasticaAdaptor;

abstract class RegistryTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Provides a ElasticaAdaptor with the normalizer decorator
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
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Assert\Assertion
     */
    protected function getAssertionObjectMock(array $methods = array())
    {
        return $this->getMockBuilder('\\Assert\\Assertion')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Provides an instance of an implementation of the RegistryInterface
     *
     * @param string $class
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|RegistryInterface
     */
    protected function getRegistryStub($class, array $methods = array())
    {
        return $this->getMockBuilder($class)
            ->disableOriginalConstructor()
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
