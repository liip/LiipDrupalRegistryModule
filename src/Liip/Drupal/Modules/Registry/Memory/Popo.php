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


}
