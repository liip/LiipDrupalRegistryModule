<?php
namespace Liip\Drupal\Modules\Registry\Lucene;

use Assert\Assertion;
use Elastica\Exception\NotFoundException;
use Liip\Drupal\Modules\DrupalConnector\Common;
use Liip\Drupal\Modules\Registry\Adaptor\Lucene\AdaptorInterface;
use Liip\Drupal\Modules\Registry\Adaptor\Lucene\ElasticaAdaptor;
use Liip\Drupal\Modules\Registry\Registry;
use Liip\Drupal\Modules\Registry\RegistryException;


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
     * @var \Liip\Drupal\Modules\Registry\Adaptor\ElasticaAdaptor
     */
    protected $adaptor;


    /**
     * @param string $section
     * @param \Liip\Drupal\Modules\DrupalConnector\Common $dcc
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Common $dcc, Assertion $assertion)
    {
        $this->validateElasticaDependency();
        $this->adaptor = $this->getESAdaptor();

        parent::__construct($section, $dcc, $assertion);

        // elastica will complain if the index name is not lowercase.
        $this->section = strtolower($this->section);

        $this->registry[$this->section] = $this->adaptor->getIndex($this->section);

    }

    /**
     * Initates a registry.
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException in case the initiation of an active registry was requested.
     */
    public function init()
    {
        if (empty($this->registry[$this->section])) {

            $this->registry[$this->section] = $this->adaptor->getIndex($this->section);
        }
    }

    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function register($identifier, $value, $type = "")
    {
        if ($this->isRegistered($identifier)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_CODE
            );
        }

        $this->adaptor->registerDocument($this->section, $value, $identifier, $type);
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

        $this->adaptor->updateDocument($identifier, $value, $this->section);
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

        $this->adaptor->removeDocuments(array($identifier), $this->section);
    }

    /**
     * Shall delete the current registry from the database.
     */
    public function destroy()
    {
        $this->registry = array();

        $this->adaptor->deleteIndex($this->section);
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
     * Verifies a document is in the elasticsearch index.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function isRegistered($identifier)
    {
        try {
           $this->adaptor->getDocument($identifier, $this->section);

        } catch (NotFoundException $e) {

            return false;
        }

        return true;
    }

    /**
     * Provides the current set of registered items.
     *
     * @return array
     */
    public function getContent()
    {
        return $this->adaptor->getDocuments($this->registry[$this->section]);
    }

    /**
     * Finds the item corresponding to the provided identifier in the registry.
     *
     * @param string $identifier
     * @param null $default
     *
     * @return array
     */
    public function getContentById($identifier, $default = null)
    {
        $index = $this->registry[$this->section];
        return $this->adaptor->getDocument($identifier, $index->getName());
    }

    /**
     * Provides an instance ot the adaptor e.g. of the Elastica library.
     *
     * @return AdaptorInterface
     */
    public function getESAdaptor()
    {
        if (empty($this->adaptor)) {

            $this->adaptor = new ElasticaAdaptor();
        }

        return $this->adaptor;
    }

    /**
     * Provides the ability to influence the used adaptor to whatever elasticsearch library.
     *
     * @param AdaptorInterface $adaptor
     */
    public function setESAdaptor(AdaptorInterface $adaptor)
    {
        $this->adaptor = $adaptor;
    }
}
