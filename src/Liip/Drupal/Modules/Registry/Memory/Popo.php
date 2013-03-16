<?php
namespace Liip\Drupal\Modules\Registry\Memory;

use Assert\Assertion;
use Liip\Drupal\Modules\DrupalConnector\Common;
use Liip\Drupal\Modules\Registry\Registry;
use Liip\Drupal\Modules\Registry\RegistryException;


class Popo extends Registry
{
    /**
     * @param string $section
     * @param \Liip\Drupal\Modules\DrupalConnector\Common $dcc
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Common $dcc, Assertion $assertion)
    {
        parent::__construct($section, $dcc, $assertion);

        $this->registry[$this->section] = array();
    }

    /**
     * Shall delete the current registry from the database.
     */
    public function destroy()
    {
        $this->registry = array();
    }

    /**
     * Initates a registry.
     *
     * @throws \netmigrosintranet\modules\Registry\Classes\RegistryException in case the initiation of an active registry was requested.
     */
    public function init()
    {
        if(! empty($this->registry[$this->section])) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
            );
        }

        $this->registry[$this->section] = array();
    }

    /**
     * Provides the current set of registered items.
     *
     * @return array
     */
    public function getContent()
    {
        return $this->registry[$this->section];
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
        $this->assertion->keyExists(
            $this->registry[$this->section],
            $identifier,
            'Requested item ('. $identifier .') is not registered in the current registry.'
        );

        return $this->registry[$this->section][$identifier];
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

        return array_key_exists($identifier, $this->registry[$this->section]);
    }

    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     * @throws RegistryException
     * @return void
     */
    public function register($identifier, $value)
    {
        if ($this->isRegistered($identifier)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_CODE
            );
        }

        $this->registry[$this->section][$identifier] = $value;
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

        $this->registry[$this->section][$identifier] = $value;
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
        unset($this->registry[$this->section][$identifier]);
    }
}
