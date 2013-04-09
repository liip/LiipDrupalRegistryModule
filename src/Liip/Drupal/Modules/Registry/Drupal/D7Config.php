<?php

namespace Liip\Drupal\Modules\Registry\Drupal;

use Assert\Assertion;
use Assert\InvalidArgumentException;
use Liip\Drupal\Modules\DrupalConnector\Common;
use Liip\Drupal\Modules\Registry\Registry;
use Liip\Drupal\Modules\Registry\RegistryException;


class D7Config extends Registry
{
    /**
     * @param string $section
     * @param \Liip\Drupal\Modules\DrupalConnector\Common $dcc
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Common $dcc, Assertion $assertion)
    {
        parent::__construct($section, $dcc, $assertion);
        $this->registry = $dcc->variable_get($section, array());
    }

    /**
     * Adds an item to the registry.
     *
     * @param string $identifier
     * @param mixed $value
     */
    public function register($identifier, $value)
    {
        parent::register($identifier, $value);
        $this->drupalCommonConnector->variable_set($this->section, $this->registry);
    }

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed $value
     */
    public function replace($identifier, $value)
    {
        parent::replace($identifier, $value);
        $this->drupalCommonConnector->variable_set($this->section, $this->registry);
    }

    /**
     * Removes an item from the regisrty.
     *
     * @param string $identifier
     *
     * @return void
     */
    public function unregister($identifier)
    {
        parent::unregister($identifier);
        $this->drupalCommonConnector->variable_set($this->section, $this->registry);
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

        if (!empty($content)) {
            throw new \InvalidArgumentException(
                "Section $this->section could not be destroyed from the registry."
            );
        }
    }

    /**
     * Initates a registry.
     *
     * @throws \netmigrosintranet\modules\Registry\Classes\RegistryException in case the initiation of an active registry was requested.
     */
    public function init()
    {
        if(! empty($this->registry)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
            );
        }

        $this->drupalCommonConnector->variable_set($this->section, $this->registry);
    }
}
