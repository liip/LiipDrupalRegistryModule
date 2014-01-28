<?php
namespace Liip\Drupal\Modules\Registry\Memory;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Registry;
use Liip\Drupal\Modules\Registry\RegistryException;


class Popo extends Registry
{
    /**
     * @param string $section
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Assertion $assertion)
    {
        parent::__construct($section, $assertion);

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
        if (! empty($this->registry[$this->section])) {
            throw new RegistryException(
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT . '(section: ' . $this->section . ')',
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
            );
        }

        $this->registry[$this->section] = array();
    }

    /**
     * Provides the current set of registered items.
     *
     *
     * NOTICE:
     *  Setting $limit to anything else than 0 (zero) currently not have any affect on the amount of returned results.
     *  This functionality is due to be implemented.
     *
     *
     * @param integer $limit  Amount of documents to be returned in result set. If set to 0 (zero) all documents of the result set will be returned. Defaults to 0.
     *
     *
     * @return array
     */
    public function getContent($limit = 0)
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
     *
     * @return bool
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
     *
     * @throws RegistryException
     */
    public function register($identifier, $value)
    {
        if ($this->isRegistered($identifier)) {
            throw new RegistryException(
                RegistryException::DUPLICATE_REGISTRATION_ATTEMPT_TEXT . '(identifier: ' . $identifier . ')',
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
     *
     * @throws RegistryException
     */
    public function replace($identifier, $value)
    {
        if (!$this->isRegistered($identifier)) {
            throw new RegistryException(
                RegistryException::MODIFICATION_ATTEMPT_FAILED_TEXT . '(identifier: ' . $identifier . ')',
                RegistryException::MODIFICATION_ATTEMPT_FAILED_CODE
            );
        }

        $this->registry[$this->section][$identifier] = $value;
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
        if (!$this->isRegistered($identifier)) {
            throw new RegistryException(
                RegistryException::UNKNOWN_IDENTIFIER_TEXT . '(identifier: ' . $identifier . ')',
                RegistryException::UNKNOWN_IDENTIFIER_CODE
            );
        }
        unset($this->registry[$this->section][$identifier]);
    }
}
