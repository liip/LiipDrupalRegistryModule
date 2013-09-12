<?php
/**
 * @file
 *   dispatches requests toward a registry to multiple registries
 */
namespace Liip\Drupal\Modules\Registry;


use Assert\Assertion;

/**
 * Class Dispatcher
 * @package LiipDrupalModulesRegistry
 */
class Dispatcher
{
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
        if (!empty($id) || 0 === $id) {

            if (!empty($this->registries[$id])) {

                throw new RegistryException(
                    RegistryException::DUPLICATE_INITIATION_ATTEMPT_TEXT,
                    RegistryException::DUPLICATE_INITIATION_ATTEMPT_CODE
                );
            }

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
     * Note:
     *   This method does handle more than just the obvious argument.
     *   Since the action callbacks do probably demand a set of arguments,
     *   it is possible to just append it to the set of arguments to this mehtod.
     *   Example:
     *   // dispatch($action, [arg1,][arg2,][argN]);
     *   $this->dispatch('register', 'entityId', array('tux' => 'gnu'));
     *   will invoke:
     *   $registry->$action('entityId', array('tux' => 'gnu'));
     *
     * @param string $action
     *
     * @return array
     */
    public function dispatch($action)
    {
        Assertion::notEmpty($this->registries, 'No registries attached!');

        // read arguments to be passed to the action callback
        $args = func_get_args();
        array_shift($args);

        $output = array();

        foreach ($this->registries as $id => $registry) {

            $output = $this->processRegistry($action, $registry, $id, $output, $args);
        }

        return $output;
    }

    /**
     * Invokes the defined callback on the given registry.
     *
     * @param string $action
     * @param Registry $registry
     * @param string $id
     * @param array $output
     * @param array $args
     *
     * @return array
     */
    protected function processRegistry($action, Registry $registry, $id, array $output, array $args = array())
    {
        try {

            $output[$id] = call_user_func_array(array($registry, $action), $args);

        } catch (RegistryException $e) {

            $this->errors[$id] = $e;
        }

        return $output;
    }

    /**
     * Provides the set of last occurred errors.
     * @return array
     */
    public function getLastErrors()
    {
        return $this->errors;
    }

    /**
     * Indicates if an error occurred during dispatching.
     * @return bool
     */
    public function hasError()
    {
        return !empty($this->errors);
    }
}
