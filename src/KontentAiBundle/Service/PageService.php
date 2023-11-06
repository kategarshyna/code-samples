<?php

namespace KontentAiBundle\Service;

use Kontent\Ai\Delivery\DeliveryClient;
use Kontent\Ai\Delivery\Models\Items\ContentItemsResponse;
use Kontent\Ai\Delivery\Models\Languages\Language;
use Kontent\Ai\Delivery\Models\Languages\LanguagesResponse;
use Kontent\Ai\Delivery\Models\Shared\Pagination;
use Kontent\Ai\Delivery\QueryParams;
use Psr\Log\LoggerInterface;
class PageService {
    private DeliveryClient $client;
    public LoggerInterface $logger;
    protected const TYPES = ['core_faq', 'core_blog', 'core_page'];

    public function __construct(string $apiKey, string $projectId, LoggerInterface $logger) {
        $this->logger = $logger;

        $this->client = new DeliveryClient(
            $projectId,
            null,
            $apiKey,
            null,
            false,
            3
        );
    }

    public function getKontentAiUpdatedPagesOnly(array $ids): array {
        $this->logger->notice('Getting updated pages from Kontent Ai API');

        /** @var ContentItemsResponse $response */
        $response = $this->client->getItems(
            (new QueryParams())
                ->in('system.id', $ids)
                ->type(self::TYPES)
        );

        $items = $response->items ?? [];
        $this->logger->notice(sprintf('Got %d pages', count($items)));

        return $items;
    }

    public function getKontentAiPages(): array {
        $items = [];
        $this->logger->notice('Getting all languages from Kontent Ai API');

        /** @var LanguagesResponse $response */
        $response = $this->client->getLanguages();

        /** @var Language $language */
        foreach ($response->languages as $language) {
            /** @var string $code */
            $code = $language->system->codename;
            $this->logger->notice(sprintf('Getting pages by language: %s', $code));
            $pagesByLng = $this->getAllPagesByLanguage($code);
            $this->logger->notice(sprintf('Got %d pages', count($pagesByLng)));
            $items = array_merge($items, $pagesByLng);
            $this->logger->notice(sprintf('Got total %d pages', count($items)));
        }

        return $items;
    }

    private function getAllPagesByLanguage(string $language): array {
        $limit = 20;
        $skip = 0;
        $itemsByLng = [];

        do {
            /** @var ContentItemsResponse $response */
            $response = $this->client->getItems(
                (new QueryParams())
                    ->language($language)
                    ->type(self::TYPES)
                    ->skip($skip)
                    ->includeTotalCount()
                    ->limit($limit)
            );
            /** @var array $items */
            $items = $response->items ?? [];
            $itemsByLng = array_merge($itemsByLng, $items);
            /** @var Pagination $pagination */
            $pagination = $response->pagination;
            $skip += $limit;
            $this->logger->notice(sprintf(
                'Got (%d/%d) pages from Kontent Ai API',
                $skip,
                $pagination->totalCount
            ));
        } while ($limit === $pagination->count);

        return array_values($itemsByLng);
    }
}
