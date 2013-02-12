<?php

namespace Liip\Drupal\Modules\Registry\Drupal;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Liip\Drupal\Modules\DrupalConnector\Common;
use Liip\Drupal\Modules\Registry\RegistryException;
use Liip\Drupal\Modules\Registry\RegistryInterface;


class D7Config implements RegistryInterface
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


    /**
     * @param \Liip\Drupal\Modules\DrupalConnector\Common $dcc
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Common $dcc, Assertion $assertion)
    {
        $this->drupalCommonConnector = $dcc;
        $this->assertion = $assertion;

        $this->verifySectionName($section);
        $this->section = $section;

        $this->registry = $dcc->variable_get($section, array());
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
     * Adds an item to the registry.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws \Assert\InvalidArgumentException in case the $value is not an instance of the World class.
     * @throws \netmigrosintranet\modules\Worlds\Classes\RegistryException in case the identifier is already used.
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
        $this->drupalCommonConnector->variable_set($this->section, $this->registry);
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
        $this->drupalCommonConnector->variable_set($this->section, $this->registry);
    }

    /**
     * Removes an item from the regisrty.
     *
     * @param string $identifier
     *
     * @throws \netmigrosintranet\modules\Worlds\Classes\RegistryException in case the identifier was not found.
     * @throws \Assert\InvalidArgumentException in case the $identifier is not a string.
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
        $this->drupalCommonConnector->variable_set($this->section, $this->registry);
    }

    /**
     * Determinesif the given identifier refers to a world.
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
     * Provides the current set of registered items.
     *
     * @return array
     */
    public function getContent()
    {
        return $this->registry;
    }

    /**
     * Finds the item corresponding to the provided identifier in the registry.
     *
     * @param $identifier
     *
     * @return mixed
     * @throws \Assert\InvalidArgumentException in case the identifier does not match a registered item.
     */
    public function getContentById($identifier)
    {
        $this->assertion->keyExists($this->registry, $identifier);

        return $this->registry[$identifier];
    }

    /**
     * Deletes the current registry from the database.
     *
     * !! Use with caution !!
     * There is no rollback.
     *
     * @throws \Assert\InvalidArgumentException in case the operation failed.
     */
    public function destroy()
    {
        $this->registry = array();
        $this->drupalCommonConnector->variable_del($this->section, $this->registry);

        $content = $this->drupalCommonConnector->variable_get($this->section, array());
        $this->assertion->notEmpty(
            $content,
            "Section $this->section could not be destroyed from the registry."
        );
    }
}
