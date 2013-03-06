<?php
namespace Liip\Drupal\Modules\Registry\Memory;

use Liip\Drupal\Modules\Registry\Registry;


class Popo extends Registry
{
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
        if(! empty($this->registry)) {
            throw new RegistryException(
                $this->drupalCommonConnector->t(RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT),
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
            );
        }

        $this->registry[$this->section] = array();
    }
}
