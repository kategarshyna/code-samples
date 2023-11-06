<?php

namespace ElasticSearchBundle\Model;

use Kontent\Ai\Delivery\Models\Items\ContentItem;
use Kontent\Ai\Delivery\Models\Items\TaxonomyTerm;
class ElasticSearchPageModel {
    private string $language = '';
    private string $type = '';
    private string $slug = '';
    private string $pageTitle = '';
    private string $pageDescription = '';
    private string $seoTitle = '';
    private string $seoDescription = '';
    private array $channels = [];

    /** @psalm-suppress UndefinedPropertyFetch
     * We expect the $page properties to be defined, and if they are undefined, we should encounter an unexpected error.
     */
    public function __construct(ContentItem $item, string $idToDelete = null) {
        /** In the case of deletion we don't have a proper ContentItem object but only the ID of it */
        if ($idToDelete) {
            $this->id = $idToDelete;

            return $this;
        }

        $this->id = $item->system->id;
        $this->language = $item->system->language;
        $this->type = $item->system->type;
        $this->slug = $item->slug;
        $this->pageTitle = $item->snippetMetaDataPageTitle;
        $this->pageDescription = $item->snippetMetaDataPageLead;
        $this->seoTitle = $item->snippetMetaDataSeoTitle;
        $this->seoDescription = $item->snippetMetaDataSeoDescription;
        $this->channels = array_values(
            array_map(
                fn(TaxonomyTerm $channel) => $channel->codename, $item->snippetClassificationChannel
            )
        );
    }

    public function getId(): string {
        return $this->id;
    }

    public function getLanguage(): string {
        return $this->language;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getSlug(): string {
        return $this->slug;
    }

    public function getPageTitle(): string {
        return $this->pageTitle;
    }

    public function getPageDescription(): string {
        return $this->pageDescription;
    }

    public function getSeoTitle(): string {
        return $this->seoTitle;
    }

    public function getSeoDescription(): string {
        return $this->seoDescription;
    }

    public function getChannels(): array {
        return $this->channels;
    }
}