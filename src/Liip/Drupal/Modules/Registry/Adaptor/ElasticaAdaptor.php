<?php
namespace Liip\Drupal\Modules\Registry\Adaptor;

use Assert\Assertion;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\BulkResponseException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\MatchAll;
use Elastica\Result;
use Elastica\Search;

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
            if (!is_array($document)) {
                $document = $this->normalizeValue($document);
            }

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
     * @param  integer|string $id document id
     * @param  mixed $data raw data for request body
     * @param  string $indexName   index to update
     * @param  string $typeName    type of index to update
     *
     * @throws ElasticaAdaptorException in case something when wrong while sending the request to elasticsearch.
     * @return \Elastica\Document
     *
     * @link http://www.elasticsearch.org/guide/reference/api/update.html
     */
    public function updateDocument($id, $data, $indexName, $typeName = '')
    {
        $index = $this->getIndex($indexName);
        $client = $index->getClient();
        $type = $index->getType(
            empty($typeName) ? $this->typeName : $typeName
        );

        // data array needs to have the key 'doc'
        $rawData = array(
            'doc' => $this->normalizeValue($data)
        );

        $response = $client->updateDocument(
            $id,
            $rawData,
            $index->getName(),
            $type->getName()
        );

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

        return $this->denormalizeValue($type->getDocument($id)->getData());
    }

    /**
     * Provides a list of all documents of the given index.
     *
     * @param \Elastica\Index $index
     *
     * @return array
     */
    public function getDocuments(Index $index)
    {
        $search = new Search($index->getClient());
        $search->addIndex($index);

        $query = new Query(new MatchAll());
        $resultSet = $search->search($query);
        $results = $resultSet->getResults();

        return $this->denormalizeValue($this->extractData($results));
    }

    /**
     * Extracts information from a nested result set.
     *
     * @param array $data
     *
     * @return array
     */
    protected function extractData(array $data)
    {
        $converted = array();

        foreach($data as $value) {

            if ($value instanceof Result) {

                $converted[$value->getId()] = $value->getData();
            }
        }

        return $converted;
    }

    /**
     * Converts a non-array value to an array
     *
     * @param mixed $value    is the "non-array" value
     * @return array          the normalized array
     */
    protected function normalizeValue($value) {
        if (!is_array($value)) {
            $key = gettype($value);

            /*
             * seems json_encode() has troubles serializing complex PHP objects.
             * Serializing before hand does solve this.
             */
            if ('object' == $key) {
                $value = serialize($value);
            }

            $array = array($key => $value);

        } else {
            return $value;
        }

        return $array;
    }

    /**
     * Converts a normalized array to the original value
     *
     * @param array $data    the expected normalized array
     * @return mixed          the normalized value
     */
    protected function denormalizeValue($data) {

        if (is_array($data) && 1 == sizeof($data)) {

            $clone = $data;
            $value = array_pop($clone);

            $ofType = gettype($value);

            if (array_key_exists($ofType, $data)) {

                $value = $data[$ofType];

            } else if (array_key_exists('object', $data)) {

                /*
                 * seems json_encode() has troubles serializing complex PHP objects.
                 * Serializing before hand does solve this.
                 * This forces a unserialize() when denormalizing.
                 */
                $value = unserialize($data['object']);

            } else {

                $value = $data;

            }

        } else {
            return $data;
        }

        return $value;
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
     * @return \Elastica\Client
     */
    public function getClient()
    {
        if (empty($this->client)) {

            $this->client = new Client();
        }

        return $this->client;
    }
}
