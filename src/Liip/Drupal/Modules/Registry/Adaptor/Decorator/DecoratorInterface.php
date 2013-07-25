<?php

namespace Liip\Drupal\Modules\Registry\Adaptor\Decorator;

/**
 * Class DecoratorInterface
 * @package LiipDrupalModulesRegistryAdaptorDecorator
 */
interface DecoratorInterface
{

    /**
     * Converts a non-array value to an array
     *
     * @param mixed $value is the "non-array" value
     *
     * @return array       the normalized array
     */
    public function normalizeValue($value);

    /**
     * Converts a normalized array to the original value
     *
     * @param array $data the expected normalized array
     *
     * @return mixed      the normalized value
     */
    public function denormalizeValue(array $data);
}
