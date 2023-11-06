<?php

namespace ElasticSearchBundle\Service;

use FOS\ElasticaBundle\Doctrine\ORM\ElasticaToModelTransformer;
use Doctrine\Common\Persistence\ManagerRegistry;

class ElasticaToModelTransformerWithoutDoctrine extends ElasticaToModelTransformer {
    public function __construct(ManagerRegistry $registry, string $objectClass = 'NOT USED', array $options = []) {
        parent::__construct($registry, $objectClass, $options);
    }

    /**
     * Rewrite transform method
     * to avoid transform of elastica objects into doctrine model objects
     *
     * @param array $elasticaObjects of elastica objects
     *
     **/
    public function transform(array $elasticaObjects) {
        return $elasticaObjects;
    }
}