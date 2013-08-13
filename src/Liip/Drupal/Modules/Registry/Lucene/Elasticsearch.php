<?php
namespace Liip\Drupal\Modules\Registry\Lucene;


use Assert\Assertion;
use Elastica\Exception\NotFoundException;
use Liip\Drupal\Modules\Registry\Registry;
use Liip\Drupal\Modules\Registry\RegistryException;
use Liip\Registry\Adaptor\Decorator\DecoratorInterface;
use Liip\Registry\Adaptor\Lucene\AdaptorInterface;
use Liip\Registry\Adaptor\Lucene\ElasticaAdaptor;

class Elasticsearch extends Registry
{
    /**
     * @var \Elastica\Client
     */
    protected $elasticaClient;
    /**
     * @var \Elastica\Index[]
     */
    protected $registry;
    /**
     * @var ElasticaAdaptor
     */
    protected $adaptor;
    /**
     * @var DecoratorInterface
     */
    protected $decorator;

    /**
     * @param string $section
     * @param Assertion $assertion
     * @param DecoratorInterface $decorator
     */
    public function __construct($section, Assertion $assertion, DecoratorInterface $decorator)
    {
        $this->validateElasticaDependency();

        // elastica will complain if the index name is not lowercase.
        $section = strtolower($section);

        parent::__construct($section, $assertion);

        $this->decorator = $decorator;
    }

    /**
     * Verifies the existence of the
     * @throws RegistryException
     */
    protected function validateElasticaDependency()
    {
        if (!class_exists('\Elastica\Index')) {

            throw new RegistryException(
                RegistryException::MISSING_DEPENDENCY_TEXT . '(dependency: \Elastica\Index)',
                RegistryException::MISSING_DEPENDENCY_CODE
            );
        }
    }

    /**
     * Provides an instance ot the adaptor e.g. of the Elastica library.
     * @return AdaptorInterface
     */
    public function getESAdaptor()
    {
        if (empty($this->adaptor)) {

            $this->assertion->isInstanceOf(
                $this->decorator,
                '\Liip\Registry\Adaptor\Decorator\DecoratorInterface',
                'Mandatory decorator object is not defined.'
            );

            $this->adaptor = new ElasticaAdaptor($this->decorator);
        }

        return $this->adaptor;
    }

    /**
     * Initiates a registry.
     */
    public function init()
    {
        $this->getRegistryIndex($this->section);
    }

    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     * @param string $type
     *
     * @throws RegistryException
     */
    public function register($identifier, $value, $type = "")
    {
        if ($this->isRegistered($identifier, $type)) {
            throw new RegistryException(
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_TEXT . '(identifier: ' . $identifier . ')',
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_CODE
            );
        }

        $this->getESAdaptor()->registerDocument($this->section, $value, $identifier, $type);
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

            $this->getESAdaptor()->getDocument($identifier, $this->section, $type);
        } catch (NotFoundException $e) {

            return false;
        }

        return true;
    }

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed $value
     * @param string $type
     *
     * @throws RegistryException
     */
    public function replace($identifier, $value, $type = "")
    {
        if (!$this->isRegistered($identifier, $type)) {
            throw new RegistryException(
                RegistryException::MODIFICATION_ATTEMPT_FAILED_TEXT . '(identifier: ' . $identifier . ')',
                RegistryException::MODIFICATION_ATTEMPT_FAILED_CODE
            );
        }

        $this->getESAdaptor()->updateDocument($identifier, $value, $this->section, $type);
    }

    /**
     * Removes an item off the register.
     *
     * @param string $identifier
     * @param string $type
     *
     * @throws RegistryException
     */
    public function unregister($identifier, $type = "")
    {
        if (!$this->isRegistered($identifier, $type)) {
            throw new RegistryException(
                RegistryException::UNKNOWN_IDENTIFIER_TEXT . '(identifier: ' . $identifier . ')',
                RegistryException::UNKNOWN_IDENTIFIER_CODE
            );
        }

        $this->getESAdaptor()->removeDocuments(array($identifier), $this->section, $type);
    }

    /**
     * Shall delete the current registry from the database.
     */
    public function destroy()
    {
        $this->registry = array();

        $this->getESAdaptor()->deleteIndex($this->section);
    }

    /**
     * Provides the current set of registered items.
     * @return array
     */
    public function getContent()
    {
        return $this->getESAdaptor()->getDocuments($this->getRegistryIndex($this->section));
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
        $index = $this->getRegistryIndex($this->section);

        return $this->getESAdaptor()->getDocument($identifier, $index->getName(), $type);
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
     * @param $section
     *
     * @return \Elastica\Index
     */
    protected function getRegistryIndex($section)
    {
        if (empty($this->registry[$section])){

            $this->registry[$section] = $this->getESAdaptor()->getIndex($section);
        }

        return $this->registry[$section];
    }
}
