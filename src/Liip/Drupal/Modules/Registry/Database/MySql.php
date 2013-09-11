<?php

namespace Liip\Drupal\Modules\Registry\Database;

use Assert\Assertion;
use Liip\Drupal\Modules\Registry\Registry;
use Liip\Drupal\Modules\Registry\RegistryException;


class MySql extends Registry
{
    /**
     * @var \PDO Database connection object
     */
    protected $mysql;

    /**
     * @param string $section
     * @param Assertion $assertion
     * @param \PDO $mysql
     */
    public function __construct($section, Assertion $assertion, \PDO $mysql)
    {
        $section = strtolower($section);

        parent::__construct($section, $assertion);

        $this->mysql = $mysql;
    }

    /**
     * Provides the current content of the registry.
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     * @return array
     */
    public function getContent()
    {
        $this->registry[$this->section] = parent::getContent();

        if (empty($this->registry[$this->section])) {

            $sql = sprintf('SELECT * FROM `%s`;', $this->mysql->quote($this->section));
            $result = $this->mysql->query($sql);

            if (false === $result) {

                $this->throwException(
                    'Failed to fetch information from the registry: ',
                    $this->mysql->errorInfo()
                );
            }

            $this->registry[$this->section] = $result->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $this->registry[$this->section];
    }

    /**
     * Provides a set of registry items.
     *
     * @param array $identifiers
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     * @return array
     */
    public function getContentByIds(array $identifiers)
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE entityId IN (`%s`);',
            $this->mysql->quote($this->section),
            implode('`,`', $identifiers)
        );
        $result = $this->mysql->query($sql);

        if (false === $result) {

            $this->throwException(
                'Error occurred while querying the registry: ',
                $this->mysql->errorInfo()
            );
        }

        $content =  $result->fetchAll(\PDO::FETCH_ASSOC);
        $this->registry[$this->section] = array_merge($this->registry[$this->section], $content);

        return $content;
    }

    /**
     * Provides the registry content identified by its ID.
     *
     * @param string $identifier
     * @param null $default
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     * @return array
     */
    public function getContentById($identifier, $default = null)
    {
        if (empty($this->registry[$this->section][$identifier])) {

            $this->registry[$this->section][$identifier] = $this->getContentByIds(array($identifier));

            if (empty($this->registry[$this->section][$identifier])) {
                return $default;
            }
        }

        return $this->registry[$this->section][$identifier];
    }

    /**
     * Registers the provided value.
     *
     * @param string $identifier
     * @param string $value
     */
    public function register($identifier, $value)
    {
        parent::register($identifier, $value);

        $sql = sprintf(
            'INSERT INTO %s (`entityId`, `data`) set (`%s`, `%s`);',
            $this->mysql->quote($this->section),
            $this->mysql->quote($identifier),
            $value
        );
        $result = $this->mysql->query($sql);

        if (false === $result) {

            $this->registry[$this->section][$identifier] = null;

            $this->throwException(
                'Error occurred while registering an entity: ',
                $this->mysql->errorInfo()
            );
        }
    }

    /**
     * @param string $identifier
     *
     * @return bool
     */
    public function isRegistered($identifier)
    {
        $entity = parent::isRegistered($identifier);

        if (empty($entity)) {
            $entity = $this->getContentById($identifier);

            if (!empty($entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $identifier
     * @param mixed $value
     */
    public function replace($identifier, $value)
    {
        $sql = sprintf(
            'UPDATE %s SET `data`=`%s` WHERE `entityId`=`%s`;',
            $this->mysql->quote($this->section),
            $this->mysql->quote($data),
            $this->mysql->quote($identifier)
        );

        $result = $this->mysql->query($sql);

        if (false === $result) {

            $this->throwException(
                'Failed to fetch information from the registry: ',
                $this->mysql->errorInfo()
            );
        }

        parent::replace($identifier, $value);
    }

    /**
     * @param string $identifier
     */
    public function unregister($identifier)
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE `entityId`=`%s`;',
            $this->mysql->quote($this->section),
            $this->mysql->quote($identifier)
        );

        $result = $this->mysql->query($sql);

        if (false === $result) {

            $this->throwException(
                'Failed to fetch information from the registry: ',
                $this->mysql->errorInfo()
            );
        }

        parent::unregister($identifier);
    }

    /**
     * Shall delete the current registry from the database.
     *
     * @throws RegistryException in case the deletion of the database failed.
     */
    public function destroy()
    {
        // delete DB
        $sql = sprintf(
            'DROP TABLE `%s`;',
            $this->mysql->quote($this->section)
        );

        if (false === $this->mysql->exec($sql)) {

            $this->throwException(
                'Unable to delete the database: ',
                $this->mysql->errorInfo()
            );
        }

        $this->registry[$this->section] = array();

    }

    /**
     * Shall register a new section in the registry
     *
     * @return array
     */
    public function init()
    {
        $this->registryTableExists();

        if (empty($this->registry[$this->section])){

            $this->registry[$this->section] = $this->getContent();
        }

        return $this->registry[$this->section];
    }

    /**
     * Validates that the registry table exists.
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    protected function registryTableExists()
    {
        $sql = sprintf(
            'SHOW CREATE TABLE `%s`;',
            $this->mysql->quote($this->section)
        );

        if (false === $this->mysql->query($sql)) {

            $this->throwException(
                'The registry table does not exists: ',
                $this->mysql->errorInfo()
            );
        }
    }

    /**
     * @param string $message
     * @param array $error
     *
     * @throws \Liip\Drupal\Modules\Registry\RegistryException
     */
    protected function throwException($message, array $error)
    {
        throw new RegistryException(
            $message . $error[2],
            $error[1]
        );
    }
}
