<?php

namespace Liip\Drupal\Modules\Registry;


use Assert\Assertion;

interface FactoryInterface
{
    /**
     * Provides an object of the requested implementation of the RegistryInterface.
     *
     * @param string $name
     * @param string $section
     * @param Assertion $assertion
     *
     * @return RegistryInterface
     */
    public function getRegistry($name, $section, Assertion $assertion);

}
