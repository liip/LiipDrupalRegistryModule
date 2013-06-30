<?php
namespace Liip\Drupal\Modules\Registry\Tests;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Lucene\Elasticsearch;
use lapistano\ProxyObject\ProxyBuilder;

abstract class RegistryTestCase extends \PHPUnit_Framework_TestCase
{

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
     * Provides a stub for the Common class of the DrupalConnector Module.
     *
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDrupalCommonConnectorMock(array $methods = array())
    {
        return $this->getMockBuilder('\\Liip\\Drupal\\Modules\\DrupalConnector\\Common')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * Provides a fixture of the Common class of the Drupal Connector
     *
     * @param array $methods
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDrupalCommonConnectorFixture(array $methods = array())
    {
        $methods = array_merge($methods, array('variable_get'));

        $drupalCommonConnector = $this->getDrupalCommonConnectorMock($methods);
        $drupalCommonConnector
            ->expects($this->once())
            ->method('variable_get')
            ->with(
            $this->isType('string'),
            $this->isType('array')
        )
            ->will(
            $this->returnValue(array())
        );

        if (in_array('variable_set', $methods)) {
            $drupalCommonConnector
                ->expects($this->once())
                ->method('variable_set')
                ->with(
                $this->isType('string')
            );
        }

        if (in_array('t', $methods)) {
            $drupalCommonConnector
                ->expects($this->once())
                ->method('t')
                ->with(
                $this->isType('string')
            );
        }

        return $drupalCommonConnector;
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
     * @param $indexName
     *
     * @return Elasticsearch
     */
    protected function getRegistryObject($indexName)
    {
        $common = $this->getDrupalCommonConnectorMock(array('t'));
        $common
            ->expects($this->any())
            ->method('t')
            ->will($this->returnArgument(0));

        $registry = new Elasticsearch(
            $indexName,
            $common,
            new Assertion()
        );

        return $registry;
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
