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
     * @param \Elastica\Document|array $document
     * @param string $identifier
     * @param string $typeName
     *
     * @throws ElasticaAdaptorException
     * @return \Elastica\Document
     */
    public function registerDocument($indexName, $document, $identifier = '', $typeName = '')
    {
        $index = $this->getIndex($indexName);
        $type = $index->getType(
            empty($typeName) ? $this->typeName : $typeName
        );

        if (!$document instanceof Document) {

            Assertion::isArray($document, 'The value of the document to be added to the index has to be of type array.');
            Assertion::notEmpty($document, 'The document data may not be empty.');

            $document = new Document($identifier, $document);
        }

        try {

            $type->addDocuments(array($document));
            $index->refresh();

        } catch (BulkResponseException $e) {
             throw new ElasticaAdaptorException($e->getMessage(), $e->getCode(), $e);
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
     * @param  string $indexName   index to update
     * @param  string $typeName    type of index to update
     *
     * @throws ElasticaAdaptorException in case something when wrong while sending the request to elasticsearch.
     * @return \Elastica\Document
     * @link http://www.elasticsearch.org/guide/reference/api/update.html
     */
    public function updateDocument($id, array $data, $indexName, $typeName = '')
    {
        $index = $this->getIndex($indexName);
        $client = $index->getClient();
        $type = $index->getType(
            empty($typeName) ? $this->typeName : $typeName
        );

        $response = $client->updateDocument($id, $data, $index->getName(), $type->getName());

        if ($response->hasError()) {

            $error = $this->normalizeError($response->getError());

            throw new ElasticaAdaptorException(
                $error->getMessage(),
                $error->getCode(),
                $error
            );
        }

        $type->getIndex()->refresh();

        return $type->getDocument($id);
    }

    /**
     * Fetches the requested document from the index.
     *
     * @param string $id
     * @param string $indexName
     * @param string $typeName
     *
     * @return \Elastica\Document
     */
    public function getDocument($id, $indexName, $typeName = '')
    {
        $index = $this->getIndex($indexName);
        $type = $index->getType(
          empty($typeName) ? $this->typeName : $typeName
        );

        return $type->getDocument($id);
    }

    /**
     * determines if the risen error is of type Exception.
     *
     * @param mixed $error
     *
     * @return ElasticaAdaptorException
     */
    public function normalizeError($error)
    {
        if ($error instanceof \Exception) {
            return new ElasticaAdaptorException($error->getMessage(), $error->getCode(), $error);
        }

        return new ElasticaAdaptorException(
            sprintf('An error accord: %s', print_r($error, true)),
            0
        );
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
