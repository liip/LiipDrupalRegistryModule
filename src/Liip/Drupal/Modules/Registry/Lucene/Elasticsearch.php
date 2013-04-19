<?php
namespace Liip\Drupal\Modules\Registry\Lucene;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Liip\Drupal\Modules\DrupalConnector\Common;
use Liip\Drupal\Modules\Registry\Registry;
use Liip\Drupal\Modules\Registry\RegistryException;
use Elastica\Client;


class Elasticsearch extends Registry
{
    /**
     * @var \Elastica\Client Instance of the elasticsearch library.
     */
    protected $elasticaClient;

    /**
     * @var \Elastica\Index[]
     */
    protected $registry;


    /**
     * @param string $section
     * @param \Liip\Drupal\Modules\DrupalConnector\Common $dcc
     * @param \Assert\Assertion $assertion
     * @param array $options
     */
    public function __construct($section, Common $dcc, Assertion $assertion, array $options)
    {
        $this->validateElasticaDependency();

        parent::__construct($section, $dcc, $assertion);

        // elastica will complain if the index name is not lowercase.
        $this->section = strtolower($section);

        $this->init();
    }

    /**
     * Initates a registry.
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException in case the initiation of an active registry was requested.
     */
    public function init()
    {
        if(! empty($this->registry[$this->section])) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
            );
        }

        $this->registry[$this->section] = $this->getElasticaIndex($this->section);
    }

    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function register($identifier, $value)
    {
        if ($this->isRegistered($identifier)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_CODE
            );
        }

        $index = $this->getElasticaIndex($this->section);
        $index->addDocuments(array(new \Elastica\Document($identifier, $value)));
        $index->refresh();
    }

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function replace($identifier, $value)
    {
        if (!$this->isRegistered($identifier)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::MODIFICATION_ATTEMPT_FAILED_TEXT),
                RegistryException::MODIFICATION_ATTEMPT_FAILED_CODE
            );
        }

        $document = new \Elastica\Document($identifier, $value, '', $this->section);
        $index = $this->getElasticaIndex($this->section);
        $index->addDocuments(array($document));
        $index->refresh();
    }

    /**
     * Removes an item off the register.
     *
     * @param string $identifier
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function unregister($identifier)
    {
        if (!$this->isRegistered($identifier)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::UNKNOWN_IDENTIFIER_TEXT),
                RegistryException::UNKNOWN_IDENTIFIER_CODE
            );
        }
    }

    /**
     * Shall delete the current registry from the database.
     */
    public function destroy()
    {
        // close and delete index using elastica
        $index = $this->getElasticaIndex($this->indexName);

        $this->registry = array();

        $index = $this->getElasticaIndex($this->section);
        $index->close();
        $index->delete();
    }

    /**
     * Verifies the existence of the
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    protected function validateElasticaDependency()
    {
        if (!class_exists('\Elastica\Index')) {

            throw new RegistryException(
                RegistryException::MISSING_DEPENDENCY_TEXT,
                RegistryException::MISSING_DEPENDENCY_CODE
            );
        }
    }

    /**
     * Provides an elastica client.
     *
     * @return \Elastica_Client
     */
    protected function getElasticaClient()
    {
        if (empty($this->elasticaClient)) {

            $this->elasticaClient = new Client();
        }

        return $this->elasticaClient;
    }

    /**
     * Provides an elasticsearch index to attach documents to.
     *
     * @param string $indexName
     *
     * @return \Elastica\Index
     */
    protected function getElasticaIndex($indexName)
    {
        if (empty($this->registry[$indexName])) {
            $client = $this->getElasticaClient();
            $this->registry[$indexName] = $client->getIndex($indexName);

            $this->registry[$indexName]->create(
                array(
                    'number_of_shards' => 5,
                    'number_of_replicas' => 1,
                )
            );
        }

        return $this->registry[$indexName];
    }
}
