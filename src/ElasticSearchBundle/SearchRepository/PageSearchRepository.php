<?php

namespace ElasticSearchBundle\SearchRepository;

use Elastica\Query\BoolQuery;
use Elastica\Query\Ids;
use Elastica\Query\MultiMatch;
use Elastica\Query\Term;
use Elastica\Query\Wildcard;

class PageSearchRepository extends BaseEasyServiceSearchRepository {

    /**
     * @param string $queryString
     * @param int $offset
     * @param int $limit
     * @param string $lng
     * @param string $channel
     *
     * @return array ['pages' => [], 'count' => int]
     */
    public function searchForPage(string $queryString, int $offset, int $limit, string $lng, string $channel): array {
        $query = new BoolQuery();

        if ($channel) {
            $query->addFilter($this->filterQuery('channels', $channel));
        }

        $query->addFilter($this->filterQuery('language', explode('-', $lng)[0]));
        $query->addShould($this->prefixQuery('type', $queryString));
        $query->addShould($this->idQuery($queryString));
        $query->addShould($this->multiMatchQuery(
            $queryString, ['pageTitle', 'pageDescription', 'seoTitle', 'seoDescription', 'slug'],
            MultiMatch::TYPE_MOST_FIELDS, '1'
        ));

        $query->setMinimumShouldMatch(1);

        return $this->search($query, $offset, $limit);
    }

    protected function filterQuery(string $key, string $value, int $boost = 1): BoolQuery {
        return (new BoolQuery())->addFilter(
            (new Term())->setTerm($key, $value, $boost)
        );
    }

    protected function prefixQuery(string $key, string $value, int $boost = 1): BoolQuery {
        return (new BoolQuery())->addShould(
            (new Wildcard())->setValue($key, '*' . $value, $boost)
        );
    }

    protected function idQuery(string $value): BoolQuery {
        return (new BoolQuery())->addShould(
            (new Ids())->setIds($value)
        );
    }
}