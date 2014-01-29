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
     * @var array
     */
    protected $indexOptions = array();
    /**
     * @var array|null
     */
    protected $specials;

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
        $this->getRegistryIndex($this->section, $this->indexOptions, $this->specials);
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
     *
     * @param integer $limit  Amount of documents to be returned in result set. If set to 0 (zero) all documents of the result set will be returned. Defaults to 0.
     *
     * @return array
     */
    public function getContent($limit = 0)
    {
        return $this->getESAdaptor()->getDocuments($this->getRegistryIndex($this->section), $limit);
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
     * Provides an elastic search index.
     *
     * @param string $section$indexOptions = array(), $specials = null
     * @param array $indexOptions
     * @param null $specials
     *
     * @return \Elastica\Index
     *
     * @link http://elastica.io/getting-started/storing-and-indexing-documents.html
     */
    protected function getRegistryIndex($section, array $indexOptions = array(), $specials = null)
    {
        if (empty($this->registry[$section])){

            $this->registry[$section] = $this->getESAdaptor()->getIndex($section, $indexOptions, $specials);
        }

        return $this->registry[$section];
    }

    /**
     * Registers the option set to be used to create the next elastic search index.
     *
     * @param array $options
     */
    public function setIndexOptions(array $options = array())
    {
        $this->indexOptions = $options;
    }

    /**
     * Reveals the current index options.
     *
     * @return array
     */
    public function getIndexOptions(){
        return $this->indexOptions;
    }

    /**
     * Defines the special options to create an elastic search index.
     *
     * @param array $options
     *
     * @see \Elastica\Index::create
     */
    public function setIndexSpecials(array $options)
    {
        $this->specials = $options;
    }

    /**
     * Reveals the current set special options for index creation.
     *
     * @return array|null
     */
    public function getIndexSpecials()
    {
        return $this->specials;
    }
}
