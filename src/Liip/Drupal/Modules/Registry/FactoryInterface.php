<?php

namespace Liip\Drupal\Modules\Registry;


use Assert\Assertion;

interface FactoryInterface
{

    public function getRegistry($name, $section, Assertion $assertion);

}
