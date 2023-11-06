<?php

namespace KontentAiBundle\ViewModel;

use Elastica\Result;

class PageViewModel {
    public string $language;
    public string $type;
    public string $slug;
    public string $pageTitle;
    public string $pageDescription;
    public string $seoTitle;
    public string $seoDescription;
    public array $channels;

    public function __construct(Result $elasticObject) {
        $page = (object)$elasticObject->getSource();

        $this->language = $page->language;
        $this->type = $page->type;
        $this->slug = $page->slug;
        $this->pageTitle = $page->pageTitle;
        $this->pageDescription = $page->pageDescription;
        $this->seoTitle = $page->seoTitle;
        $this->seoDescription = $page->seoDescription;
        $this->channels = $page->channels;
    }
}