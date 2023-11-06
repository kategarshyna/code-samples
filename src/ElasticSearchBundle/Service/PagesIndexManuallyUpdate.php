<?php

namespace ElasticSearchBundle\Service;

use CoreBundle\Service\Traits\LoggerAwareTrait;
use FOS\ElasticaBundle\Persister\ObjectPersister;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class PagesIndexManuallyUpdate implements LoggerAwareInterface {
    protected LoggerInterface $logger;
    protected ObjectPersister $pagesPersister;

    public function __construct(ObjectPersister $pagesPersister, LoggerInterface $logger) {
        $this->pagesPersister = $pagesPersister;
        $this->logger = $logger;
    }

    public function update(array $pages): void {
        $this->pagesPersister->replaceMany(
            $pages
        );
    }

    public function delete(array $pages): void {
        $this->pagesPersister->deleteMany(
            $pages
        );
    }
}