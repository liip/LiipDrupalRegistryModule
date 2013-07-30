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
     * @var \Liip\Drupal\Modules\DrupalConnector\Common
     */
    protected $drupalCommonConnector;

    /**
     * @param string $section
     * @param \Assert\Assertion $assertion
     */
    public function __construct($section, Assertion $assertion)
    {
        parent::__construct($section, $assertion);
    }

    /**
     * Adds an item to the registry.
     *
     * @param string $identifier
     * @param mixed $value
     */
    public function register($identifier, $value)
    {
        $this->load();

        parent::register($identifier, $value);
        $this->getDrupalCommonConnector()->variable_set($this->section, $this->registry[$this->section]);
    }

    /**
     * Loads the current content of the registry.
     */
    private function load()
    {
        $this->registry[$this->section] = $this->getDrupalCommonConnector()->variable_get($this->section, array());
    }

    /**
     * Provides an instacne of the LiipDrupalConnectorCommon class.
     *
     * @return \Liip\Drupal\Modules\DrupalConnector\Common
     */
    public function getDrupalCommonConnector()
    {
        if (empty($this->drupalCommonConnector)) {

            $this->drupalCommonConnector = new Common();
        }

        return $this->drupalCommonConnector;
    }

    /**
     * Sets the given connector object as current.
     *
     * @param Common $dcc
     */
    public function setDrupalCommonConnector(Common $dcc)
    {
        $this->drupalCommonConnector = $dcc;
    }

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed  $value
     */
    public function replace($identifier, $value)
    {
        $this->load();

        parent::replace($identifier, $value);
        $this->getDrupalCommonConnector()->variable_set($this->section, $this->registry);
    }

    /**
     * Removes an item from the regisrty.
     *
     * @param string $identifier
     */
    public function unregister($identifier)
    {
        $this->load();

        parent::unregister($identifier);
        $this->getDrupalCommonConnector()->variable_set($this->section, $this->registry);
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
        $this->registry[$this->section] = array();
        $dcc = $this->getDrupalCommonConnector();

        $dcc->variable_del($this->section, $this->registry);

        $content = $dcc->variable_get($this->section, array());

        if (!empty($content)) {
            throw new \InvalidArgumentException(
                "Section $this->section could not be destroyed from the registry."
            );
        }
    }

    /**
     * Initiates a registry.
     *
     * @throws RegistryException
     */
    public function init()
    {
        $this->load();

        if (!empty($this->registry[$this->section])) {
            throw new RegistryException(
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT . '(section: ' . $this->section . ')',
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
            );
        }

        $this->getDrupalCommonConnector()->variable_set($this->section, $this->registry[$this->section]);
    }
}
