<?php
namespace Liip\Drupal\Modules\Registry;

use Assert\Assertion;
use Liip\Drupal\Modules\DrupalConnector\Common;

abstract class Registry implements RegistryInterface
{
    /**
     * Provides an API defining a set of assertions.
     * @var \Assert\Assertion $assertion
     */
    protected $assertion;

    /**
     * Provides an API to use D7 functions in an OOP perspective.
     * @var \Liip\Drupal\Modules\DrupalConnector\Common
     */
    protected $drupalCommonConnector;

    /**
     * List of registered items..
     * @var array
     */
    protected $registry = array();

    /**
     * Name of the section in the registry to be altered.
     * @var string
     */
    protected $section = '';

//    /**
//     * Shall delete the current registry from the database.
//     */
//    abstract public function destroy();
//
//    /**
//     * Shall register a new section in the registry
//     */
//    abstract public function init();

    /**
     * @param string $section
     * @param \Liip\Drupal\Modules\DrupalConnector\Common $dcc
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Common $dcc, Assertion $assertion)
    {
        $this->drupalCommonConnector = $dcc;
        $this->assertion = $assertion;

        $this->verifySectionName($section);
        $this->section = $section;

        $this->registry = array();
    }

    /**
     * Verifies the validity of the section to be managed.
     *
     * @param $section
     *
     * @throws \Assert\InvalidArgumentException in case the provided string does not fit the requirements.
     */
    protected function verifySectionName($section)
    {
        $this->assertion->notEmpty(
            $section,
            'The name of the section must not be empty.'
        );
        $this->assertion->string(
            $section,
            'The name of the section must be a string.'
        );
    }

    /**
     * Provides the current set of registered items.
     *
     * @return array
     */
    public function getContent()
    {
        return $this->registry;
    }

    /**
     * Determines if the given identifier refers to a world.
     *
     * @param string $identifier
     * @return bool
     *
     * @throws \Assert\InvalidArgumentException in case the $identifier is not a string.
     */
    public function isRegistered($identifier)
    {
        $this->verifySectionName($identifier);

        return array_key_exists($identifier, $this->registry);
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
        $this->assertion->keyExists($this->registry, $identifier);

        return $this->registry[$identifier];
    }

    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     */
    public function register($identifier, $value)
    {
        if ($this->isRegistered($identifier)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_CODE
            );
        }

        $this->registry[$identifier] = $value;
    }

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed $value
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

        $this->registry[$identifier] = $value;
    }

    /**
     * Removes an item off the register.
     *
     * @param string $identifier
     */
    public function unregister($identifier)
    {
        if (!$this->isRegistered($identifier)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::UNKNOWN_IDENTIFIER_TEXT),
                RegistryException::UNKNOWN_IDENTIFIER_CODE
            );
        }
        unset($this->registry[$identifier]);
    }

}
