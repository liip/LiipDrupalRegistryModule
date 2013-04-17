<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lapistano
 * Date: 4/17/13
 * Time: 10:15 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Liip\Drupal\Modules\Registry\Adaptor;

use Assert\Assertion;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\BulkResponseException;

class ElasticaAdaptor
{
    /**
     * @var \Elastica\Index[]
     */
    protected $indexes;
    /**
     * @var \Elastica\Client
     */
    protected $client;
    /**
     * @var string Name of the standard type
     */
    protected $typeName = 'collab';


    /**
     * Adds a document to an index.
     *
     * @param string $indexName
     * @param \Elastica\Document|array $value
     * @param string $identifier
     * @param string $type
     *
     * @return \Elastica\Document
     */
    public function registerDocument($indexName, $value, $identifier = '', $type = '')
    {
        if (!$value instanceof Document) {

            Assertion::isArray($value, 'The value of the document to be added to the index has to be of type array.');
            Assertion::notEmpty($value, 'The document data may not be empty.');

            if (empty($type)) {
                $type = $this->typeName;
            }

            $document = new Document($identifier, $value, $type, $indexName);
        }

        $index = $this->getIndex($indexName);

        try {

            $index->addDocuments(array($document));
            $index->refresh();
        } catch (BulkResponseException $e) {
            var_dump($e->getMessage());

            // throw new ElasticaAdaptorException($e->getMessage(), $e->getCode(), $e);
        }

        return $document;
    }

    /**
     * Removes a document from the index.
     *
     * @param array $ids
     * @param string $index
     * @param string $type
     */
    public function removeDocuments(array $ids, $index, $type = '')
    {
        if (empty($type)) {
            $type = $this->typeName;
        }

        $client = $this->getClient();
        $client->deleteIds($ids, $index, $type);
    }

    /**
     * Updates a elsaticsearch document.
     *
     * @param  integer $id document id
     * @param  array $data raw data for request body
     * @param  string $index   index to update
     * @param  string $type    type of index to update
     *
     * @return boolean
     * @link http://www.elasticsearch.org/guide/reference/api/update.html
     */
    public function updateDocument($id, array $data, $index, $type = '')
    {
        $client = $this->getClient();

        if (empty($type)) {
            $type = $this->typeName;
        }

        try {
            $response = $client->updateDocument($id, $data, $index, $type);

            return $response->hasError();

        } catch (\Elastica\Exception\InvalidException $e) {

            return false;
        }
    }

    /**
     * Provides an elasticsearch index to attach documents to.
     *
     * @param string $indexName
     *
     * @return \Elastica\Index
     */
    public function getIndex($indexName)
    {
        $indexName = strtolower($indexName);

        if (empty($this->indexes[$indexName])) {

            $client = $this->getClient();
            $this->indexes[$indexName] = $client->getIndex($indexName);

            if (!$this->indexes[$indexName]->exists()) {

                $this->indexes[$indexName]->create(
                    array(
                        'number_of_shards'   => 5,
                        'number_of_replicas' => 1,
                    )
                );
            }
        }

        return $this->indexes[$indexName];
    }

    /**
     * Deletes the named index from the cluster.
     *
     * @param string $name
     */
    public function deleteIndex($name)
    {
        $client = $this->getClient();

        $index = $client->getIndex($name);
        $index->close();
        $index->delete();
    }

    /**
     * Provides an elastica client.
     * @return \Elastica_Client
     */
    public function getClient()
    {
        if (empty($this->client)) {

            $this->client = new Client();
        }

        return $this->client;
    }
}
