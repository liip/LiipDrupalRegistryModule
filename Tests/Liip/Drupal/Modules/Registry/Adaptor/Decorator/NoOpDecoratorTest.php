<?php

namespace Liip\Drupal\Modules\Registry\Adaptor\Decorator;

use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class NoOpDecoratorTest extends RegistryTestCase
{

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\Decorator\NoOpDecorator::normalizeValue
     */
    public function testNormalizeValue()
    {
        $decorator = new NoOpDecorator();

        $value = 'tux';
        
        $this->assertSame($value, $decorator->normalizeValue($value));
    }

    /**
     * @covers \Liip\Drupal\Modules\Registry\Adaptor\Decorator\NoOpDecorator::denormalizeValue
     */
    public function testDenormalizeArray()
    {
        $decorator = new NoOpDecorator();

        $value = array('tux');

        $this->assertSame($value, $decorator->denormalizeValue($value));
    }

}
