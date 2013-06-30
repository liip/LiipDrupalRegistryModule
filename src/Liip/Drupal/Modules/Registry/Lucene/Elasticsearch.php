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
     * @var array Mapping what type a registered content has
     */
    protected $typeMap = array();


    /**
     * @param string $section
     * @param \Liip\Drupal\Modules\DrupalConnector\Common $dcc
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Common $dcc, Assertion $assertion)
    {
        $this->validateElasticaDependency();
        $this->adaptor = $this->getESAdaptor();

        // elastica will complain if the index name is not lowercase.
        $this->section = strtolower($this->section);

        parent::__construct($section, $dcc, $assertion);

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
     * @param string $type
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function register($identifier, $value, $type = "")
    {
        if ($this->isRegistered($identifier, $type)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_CODE
            );
        }

        $this->determineContentTypeMapping($identifier, $value);

        $this->adaptor->registerDocument($this->section, json_encode($value), $identifier, $type);
    }

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed $value
     * @param string $type
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function replace($identifier, $value, $type = "")
    {
        if (!$this->isRegistered($identifier, $type)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::MODIFICATION_ATTEMPT_FAILED_TEXT),
                RegistryException::MODIFICATION_ATTEMPT_FAILED_CODE
            );
        }

        $this->determineContentTypeMapping($identifier, $value);

        $this->adaptor->updateDocument($identifier, json_encode($value), $this->section, $type);
    }

    /**
     * Removes an item off the register.
     *
     * @param string $identifier
     * @param string $type
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function unregister($identifier, $type = "")
    {
        if (!$this->isRegistered($identifier, $type)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::UNKNOWN_IDENTIFIER_TEXT),
                RegistryException::UNKNOWN_IDENTIFIER_CODE
            );
        }

        $this->adaptor->removeDocuments(array($identifier), $this->section, $type);
        unset($this->typeMap[$identifier]);
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
     * @param string $type
     *
     * @return bool
     */
    public function isRegistered($identifier, $type = "")
    {
        try {
            $this->adaptor->getDocument($identifier, $this->section, $type);
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
     * @param string $default
     * @param string $type
     *
     * @return array
     */
    public function getContentById($identifier, $default = "", $type = "")
    {
        $index = $this->registry[$this->section];
        $document = $this->adaptor->getDocument($identifier, $index->getName(), $type);

        return json_decode($document, $this->contentAsArray($identifier));
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

    /**
     * Determines the type of the given value.
     *
     * @param $id
     * @param $value
     */
    protected function determineContentTypeMapping($id, $value)
    {
        $type = 'array';

        // determine type of $value
        if (!is_array($value)) {
            $type = gettype($value);
        }

        $this->typeMap[$id] = $type;
    }

    /**
     * Determines if the type of the stored content identified by id is an array or not.
     *
     * @param string $id
     *
     * @return bool
     */
    protected function contentAsArray($id)
    {
        return ($this->typeMap[$id] === 'array')? true : false;
    }
}
