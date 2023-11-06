<?php

namespace ElasticSearchBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use ElasticSearchBundle\Model\ElasticSearchPageModel;
use KontentAiBundle\Service\PageService;
use FOS\ElasticaBundle\Provider\PagerfantaPager;
use FOS\ElasticaBundle\Provider\PagerProviderInterface;
use Pagerfanta\Doctrine\Collections\CollectionAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;

class PageProvider implements PagerProviderInterface {
    protected PageService $pageService;
    protected LoggerInterface $logger;

    public function __construct(PageService $pageService, LoggerInterface $logger) {
        $this->pageService = $pageService;
        $this->logger = $logger;
    }

    /**
     * @throws \Exception
     */
    public function provide(array $options = []) {
        $pages = $this->pageService->getKontentAiPages();
        $pagesCount = count($pages);

        if ($pagesCount === 0) {
            throw new \Exception('No pages returned from Kontent AI API! Reindexing is stopped!');
        }

        $pageModels = [];
        foreach ($pages as $i => $page) {
            $pageModels[] = new ElasticSearchPageModel($page);

            /** @var int $i */
            if ($i % 100 === 0) {
                $this->logger->notice(sprintf('Pages indexing progress: (%d/%d)', $i, $pagesCount));
            }
        }

        return new PagerfantaPager(new Pagerfanta(new CollectionAdapter(new ArrayCollection($pageModels))));
    }
}