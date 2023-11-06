<?php

namespace ElasticSearchBundle\SearchRepository;

use Elastica\Exception\Connection\HttpException;
use Elastica\Exception\ResponseException;
use Elastica\Query\BoolQuery;
use Elastica\Query\MultiMatch;
use Elastica\Query\Wildcard;
use Elastica\Search;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use FOS\ElasticaBundle\Repository;
use Psr\Log\LoggerInterface;

class BaseEasyServiceSearchRepository extends Repository {
    protected LoggerInterface $logger;

    public function __construct(PaginatedFinderInterface $finder, LoggerInterface $logger) {
        parent::__construct($finder);
        $this->logger = $logger;
    }

    protected function multiMatchQuery(
        string $queryString,
        array $fields,
        string $type = '',
        string $fuzziness = '',
        string $operator = MultiMatch::OPERATOR_OR
    ): MultiMatch {
        $query = (new MultiMatch())->setFields($fields)
            ->setQuery($queryString)
            ->setOperator($operator);

        if ($type) {
            $query->setType($type);
        }

        if ($fuzziness) {
            $query->setFuzziness($fuzziness);
        }

        return $query;
    }

    /**
     * @return array [$result => [], $count => int]
     */
    protected function search(array $queryParts, int $offset, int $limit): array {
        $boolQuery = new BoolQuery();

        foreach ($queryParts as $part) {
            $boolQuery->addShould($part);
        }

        $boolQuery->setMinimumShouldMatch(1);

        try {
            $paginator = $this->findPaginated($boolQuery, [
                Search::OPTION_SEARCH_TYPE => Search::OPTION_SEARCH_TYPE_DFS_QUERY_THEN_FETCH
            ]);
            $result = $paginator->getAdapter()->getSlice($offset, $limit);
            $count = $paginator->getNbResults();
        } catch (ResponseException|HttpException $exception) {
            $this->logger->error(sprintf('ElasticSearch error: %d', $exception->getMessage()), [$exception]);
            $result = [];
            $count = 0;
        }

        return [$result, $count];
    }
}