<?php

namespace Liip\Drupal\Modules\Registry\Adaptor\Decorator;

/**
 * Class NormalizeDecorator
 *
 * @package LiipDrupalModulesRegistryAdaptorDecorator
 */
class NormalizeDecorator implements DecoratorInterface
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

        if (empty($value)) {
            return $value;
        }

        return array(gettype($value) => json_encode($value));
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
        $processed = array();

        foreach ($data as $docId => $content) {
            $cloned = $content;
            $keys = array_keys($cloned);
            $ofType = array_pop($keys);
            $asArray = ('array' == $ofType)? true : false;

            if (array_key_exists($ofType, $content)) {
                $value = $content[$ofType];
            } else {
                $value = $content;
            }

            $processed[$docId] = json_decode($value, $asArray);
        }

        return $processed;
    }
}
