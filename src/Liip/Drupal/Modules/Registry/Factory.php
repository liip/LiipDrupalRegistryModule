<?php

namespace Liip\Drupal\Modules\Registry;


use Assert\Assertion;
use Assert\InvalidArgumentException;
use Liip\Drupal\Modules\Registry\Database\MySql;
use Liip\Drupal\Modules\Registry\Drupal\D7Config;
use Liip\Drupal\Modules\Registry\Lucene\Elasticsearch;
use Liip\Drupal\Modules\Registry\Memory\Popo;
use Liip\Registry\Adaptor\Decorator\DecoratorInterface;
use Liip\Registry\Adaptor\Decorator\NoOpDecorator;

class Factory implements FactoryInterface
{
    /**
     * @var Registry[]
     */
    protected $instances = array();
    /**
     * @var array
     */
    protected $configuration = array();
    /**
     * @var \PDO
     */
    protected $pdo;
    /**
     * @var DecoratorInterface
     */
    protected $decorator;


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
            $this->instances[$name] = $this->getInstanceOf($name, $section, $assertion);
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

                $config = $this->getConfiguration();
                $pdo = $this->getPdo($config['database']);
                $decorator = $this->getDecorator($config['decorator']);

                $registry = new MySql($section, $assertion, $pdo, $decorator);
                break;
            case 'd7config':
                $registry = new D7Config($section, $assertion);
                break;
            case 'elasticsearch':

                $config = $this->getConfiguration();
                $decorator = $this->getDecorator($config['decorator']);

                $registry = new Elasticsearch($section, $assertion, $decorator);
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
     * Provides a previous set configuration.
     *
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Sets a configuration to be used to instantiate registries.
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param array $configuration
     *
     * @return \PDO
     */
    public function getPdo(array $configuration)
    {
        if (empty($this->pdo)) {

            // determine amount of arguments to be passed to constructor
            if (!empty($configuration['user'])) {

                if(!empty($configuration['password'])) {
                    $this->pdo = new \Pdo($configuration['dsn'], $configuration['user'], $configuration['password']);
                } else {
                    $this->pdo = new \Pdo($configuration['dsn'], $configuration['user']);
                }
            } else {

                $this->pdo = new \PDO($configuration['dsn']);
            }
        }

        return $this->pdo;
    }

    /**
     * Sets the database connection to be used.
     *
     * @param \PDO $pdo
     */
    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Provides an instance of an implementation of the DecoratorInterface.
     *
     * @param array $configuration
     *
     * @return NoOpDecorator
     */
    public function getDecorator(array $configuration = array())
    {
        if (empty($this->decorator)) {

            if (empty($configuration['name'])) {
                $this->decorator = new NoOpDecorator();
            } else {
                $this->decorator = new $configuration['name']();
            }

        }
        return $this->decorator;
    }

    /**
     * Sets the decorator to be used.
     *
     * @param DecoratorInterface $decorator
     */
    public function setDecorator(DecoratorInterface $decorator)
    {
        $this->decorator = $decorator;
    }
}
