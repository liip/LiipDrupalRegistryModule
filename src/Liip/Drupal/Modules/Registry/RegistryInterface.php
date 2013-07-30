<?php

namespace Liip\Drupal\Modules\Registry;

interface RegistryInterface
{
    /**
     * Adds an item to the register.
     *
     * @param string $identifier
     * @param mixed $value
     */
    public function register($identifier, $value);

    /**
     * Replaces the content of the item identified by it's registration key by the new value.
     *
     * @param string $identifier
     * @param mixed $value
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    public function replace($identifier, $value);

    /**
     * Removes an item off the register.
     *
     * @param string $identifier
     */
    public function unregister($identifier);

    /**
     * Determines if the given identifier refers to an item in the register.
     *
     * @param string $identifier
     *
     * @return bool
     */
    public function isRegistered($identifier);

    /**
     * Shall provide the current set of registered items.
     * @return array
     */
    public function getContent();

    /**
     * Shall find the registry item corresponding to the provided identifier.
     *
     * @param string $identifier
     * @param null $default
     *
     * @return mixed
     */
    public function getContentById($identifier, $default = null);

    /**
     * Shall find the registry items corresponding to the provided list of identifiers.
     *
     * @param array $identifiers
     *
     * @return array
     */
    public function getContentByIds(array $identifiers);

    /**
     * Shall delete the current registry from the database.
     */
    public function destroy();

    /**
     * Shall register a new section in the registry
     */
    public function init();
}
