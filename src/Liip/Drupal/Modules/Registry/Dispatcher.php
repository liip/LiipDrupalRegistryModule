<?php
/**
 * @file
 *   dispatches requests toward a registry to multiple registries
 */
namespace Liip\Drupal\Modules\Registry;

/**
 * Class Dispatcher
 * @package LiipDrupalModulesRegistry
 */
class Dispatcher {

    /**
     * @var array
     */
    protected $registries = array();
    /**
     * @var array
     */
    protected $errors = array();


    /**
     * Adds the provided registry to the dispatcher queue.
     *
     * @param Registry $registry
     * @param string $id
     *
     * @throws RegistryException
     */
    public function attach(Registry $registry, $id = '')
    {
        if ((!empty($id) || 0 === $id) && !empty($this->registries[$id])) {
            throw new RegistryException(
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT,
                RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
            );
        }

        if (!empty($id) || 0 === $id) {
            $this->registries[$id] = $registry;
        } else {
            $this->registries[] = $registry;
        }
    }

    /**
     * Removes the a registry from dispatcher.
     *
     * @param string $id
     *
     * @throws RegistryException
     */
    public function detach($id)
    {
        if (empty($this->registries[$id])) {
            throw new RegistryException(
                RegistryException::MODIFICATION_ATTEMPT_FAILED_TEXT,
                RegistryException::MODIFICATION_ATTEMPT_FAILED_CODE
            );
        }

        unset($this->registries[$id]);
    }

    /**
     * Runs the requested action on each attached registry.
     *
     * @param string $action
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function dispatch($action)
    {
        if (empty($this->registries)) {
            throw new \InvalidArgumentException('No registries attached!');
        }

        $output = array();

        foreach ($this->registries as $id => $registry) {

            try {
                $args = func_get_args();
                array_shift($args);

                $output[$id] = call_user_func_array(array($registry, $action), $args);
            } catch (RegistryException $e) {
                $this->errors[$id] = $e;
            }
        }

        return $output;
    }

    /**
     * Provides the set of last occurred errors.
     *
     * @return array
     */
    public function getLastErrors()
    {
        return $this->errors;
    }

    /**
     * Indicates if an error occurred during dispatching.
     *
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->errors);
    }
}
