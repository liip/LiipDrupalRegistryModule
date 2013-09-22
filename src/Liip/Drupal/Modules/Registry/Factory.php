<?php

namespace Liip\Drupal\Modules\Registry;


use Assert\Assertion;
use Assert\InvalidArgumentException;
use Liip\Drupal\Modules\Registry\Memory\Popo;

class Factory implements FactoryInterface
{
    /**
     * @var  Dispatcher
     */
    protected $dispatcher;

    /**
     * @var Registry[]
     */
    protected $instances = array();


    /**
     * @param array $registryNames
     */
    public function __construct(array $registryNames)
    {
        $this->registryIds = $registryNames;
    }

    /**
     * Provides an instance of an implementation of the RegistryInterface.
     *
     * @param string $name
     * @param string $section
     * @param \Assert\Assertion $assertion
     *
     * @return RegistryInterface
     */
    public function getRegistry($name, $section, Assertion $assertion)
    {
        if (empty($this->instances[$name])) {
            $this->instances = $this->getInstanceOf($name, $section, $assertion);
        }

        return $this->instances[$name];
    }

    /**
     * Provides an instance of the requested registry.
     *
     * @param string $name
     * @param $section
     * @param \Assert\Assertion $assertion
     *
     * @throws \Assert\InvalidArgumentException
     * @return RegistryInterface
     */
    protected function getInstanceOf($name, $section, Assertion $assertion)
    {
        switch(strtolower($name)) {
            case 'mysql':
                $registry = '';
                break;
            case 'd7config':
                $registry = '';
                break;
            case 'elasticsearch':
                $registry = '';
                break;
            case 'popo':
                $registry = new Popo($section, $assertion);
                break;
            default:
                throw new InvalidArgumentException(
                    'The requested registry is not supported.',
                    Assertion::INVALID_CHOICE
                );
        }

        return $registry;
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
