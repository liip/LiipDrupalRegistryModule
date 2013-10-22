<?php

namespace Liip\Drupal\Modules\Registry;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Multiply extends Registry implements LoggerAwareInterface
{
    /**
     * @var Registry[]
     */
    protected $registries = array();
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var FactoryInterface
     */
    protected $factory;
    /**
     * @var  Dispatcher
     */
    protected $dispatcher;


    /**
     * @param string $section
     * @param Assertion $assertion
     * @param array $registries
     */
    public function __construct($section, Assertion $assertion, array $registries)
    {
        parent::__construct($section, $assertion);

        $this->registries = $registries;
    }

    /**
     * Shall delete the current registry from the database.
     */
    public function destroy()
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->dispatch('destroy');

        if ($dispatcher->hasError()) {
            throw new RegistryException($dispatcher->getLastErrorMessages());
        }
    }

    /**
     * Shall register a new section in the registry
     */
    public function init()
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->dispatch('init');

        if ($dispatcher->hasError()) {
            throw new RegistryException($dispatcher->getLastErrorMessages());
        }
    }

    /**
     * Provides the current set of registered items.
     * @return array
     */
    public function getContent()
    {
        $content = parent::getContent();

        if (empty($content)) {

            $iteration = 0;
            $content = $this->readUntilNotEmpty($iteration);
            $this->registry[$this->section] = $content;
        }

        return $content;
    }

    /**
     * Iterates over every registered registry and trys to find some content.
     *
     * @param integer $it
     *
     * @return array
     */
    protected function readUntilNotEmpty($it)
    {
        try {

            /** @var Registry $registry */
            $registry = $this->getFactory()->getRegistry($it, $this->section, $this->assertion);

        } catch (InvalidArgumentException $e) {

            $this->getLogger()->warning($e->getMessage());
            return array();
        }

        $content = $registry->getContent();

        if (empty($content)) {

            $this->getLogger()->warning('The registry ("' . get_class($registry) . '") did not provide any content.');
            $content = $this->readUntilNotEmpty(++$it);
        }

        return $content;
    }

    /**
     * Finds the item corresponding to the provided identifier in the registry.
     *
     * @param string $identifier
     * @param null $default
     *
     * @return mixed
     */
    public function getContentById($identifier, $default = null)
    {
        try {
            $content = parent::getContentById($identifier, $default);

        } catch (InvalidArgumentException $e) {

            $this->getLogger()->notice($e->getMessage());
            $content = array();
        }

        if (empty($content)) {

            $contents = $this->getContent();

            if (!empty($contents[$identifier])) {

                $content = $contents[$identifier];
            } else {

                return $default;
            }
        }

        return $content;
    }

    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws RegistryException
     */
    public function register($identifier, $value)
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->dispatch('register', $identifier, $value);

        if ($dispatcher->hasError()) {
            throw new RegistryException($dispatcher->getLastErrorMessages());
        }

        parent::register($identifier, $value);
    }

    /**
     * Determines if the given identifier refers to a registry item.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function isRegistered($identifier)
    {
        if (!parent::isRegistered($identifier)) {

            $this->getContent();

            return parent::isRegistered($identifier);
        }

        return true;
    }

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws RegistryException
     */
    public function replace($identifier, $value)
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->dispatch('replace', $identifier, $value);

        if ($dispatcher->hasError()) {
            throw new RegistryException($dispatcher->getLastErrorMessages());
        }

        parent::replace($identifier, $value);
    }

    /**
     * Removes an item off the register.
     *
     * @param string $identifier
     *
     * @throws RegistryException
     */
    public function unregister($identifier)
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->dispatch('unregister', $identifier);

        if ($dispatcher->hasError()) {
            throw new RegistryException($dispatcher->getLastErrorMessages());
        }

        parent::unregister($identifier);
    }

    /**
     * Sets a logger instance on the object
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Provides an instance of an implementation to the Psr\Log\LoggerInterface;
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        if (empty($this->logger)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * Sets a factory instance on the object
     *
     * @param FactoryInterface $factory
     *
     * @return null
     */
    public function setFactory(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Provides an instance of an implementation to the FactoryInterface;
     * @return FactoryInterface
     */
    public function getFactory()
    {
        if (empty($this->factory)) {
            $this->factory = new Factory($this->registries);
        }

        return $this->factory;
    }

    /**
     * Provides an instance of the Dispatcher;
     *
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        if (empty($this->dispatcher)) {
            $this->dispatcher = new Dispatcher();

            foreach($this->registries as $registryName) {

                $this->dispatcher->attach(
                    $this->getFactory()->getRegistry($registryName, $this->section,  $this->assertion),
                    $registryName
                );
            }
        }

        return $this->dispatcher;
    }

    /**
     * Sets the dispatcher to be used to run an action on a set of registries;
     *
     * @param Dispatcher $dispatcher
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
}
