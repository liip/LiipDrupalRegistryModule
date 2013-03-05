<?php
namespace Liip\Drupal\Modules\Registry\Memory;

use Liip\Drupal\Modules\Registry\Tests\RegistryTestCase;

class PopoTest extends RegistryTestCase
{
    /**
     * @covers \Liip\Drupal\Modules\Registry\Memory\Popo::destroy
     */
    public function testDestroy()
    {
        $registry = new Popo(
            'mySection',
            $this->getDrupalCommonConnectorMock(),
            $this->getAssertionObjectMock()
        );

        $registry->register('Tux', 'devil');
        $registry->destroy();

        $this->assertAttributeEquals(array(), 'registry', $registry);
    }
}
