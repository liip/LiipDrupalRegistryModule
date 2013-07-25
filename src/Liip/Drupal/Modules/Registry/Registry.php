<?php
namespace Liip\Drupal\Modules\Registry;

use Assert\Assertion;
use Assert\InvalidArgumentException;

abstract class Registry implements RegistryInterface
{
    /**
     * Provides an API defining a set of assertions.
     * @var \Assert\Assertion $assertion
     */
    protected $assertion;
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

    /**
     * @param string $section
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Assertion $assertion)
    {
        $this->assertion = $assertion;

        $this->verifySectionName($section);
        $this->section = $section;

        $this->registry = array($section => array());
    }

    /**
     * Verifies the validity of the section to be managed.
     *
     * @param $section
     *
     * @throws InvalidArgumentException
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
     * @return array
     */
    public function getContent()
    {
        return $this->registry[$this->section];
    }

    /**
     * Shall find the registry items corresponding to the provided list of identifiers.
     *
     * @param array $identifiers
     *
     * @return array
     */
    public function getContentByIds(array $identifiers)
    {
        $items = array();

        foreach ($identifiers as $id) {

            $items[$id] = $this->getContentById($id);
        }

        return $items;
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
            sprintf(
                'Requested item (%s) is not registered in the current section (%s) of the registry.',
                $identifier,
                $this->section
            )
        );

        return $this->registry[$this->section][$identifier];
    }

    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws RegistryException
     * @return void
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
     * Determines if the given identifier refers to a registry item.
     *
     * @param string $identifier
     *
     * @return bool
     * @throws \Assert\InvalidArgumentException in case the $identifier is not a string.
     */
    public function isRegistered($identifier)
    {
        $this->verifySectionName($identifier);

        return array_key_exists($identifier, $this->registry[$this->section]);
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
