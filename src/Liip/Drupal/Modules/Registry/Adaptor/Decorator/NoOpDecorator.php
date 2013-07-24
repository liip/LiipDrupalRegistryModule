<?php
/**
 * Created by JetBrains PhpStorm.
 * User: parsemall
 * Date: 7/24/13
 * Time: 3:42 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Liip\Drupal\Modules\Registry\Adaptor\Decorator;


class NoOpDecorator implements DecoratorInterface
{
    /**
     * Converts a non-array value to an array
     *
     * @param mixed $value is the "non-array" value
     *
     * @return array       the normalized array
     */
    public function normalizeValue($value)
    {
        return $value;
    }

    /**
     * Converts a normalized array to the original value
     *
     * @param array $data the expected normalized array
     *
     * @return mixed      the normalized value
     */
    public function denormalizeValue(array $data)
    {
        return $data;
    }

}
