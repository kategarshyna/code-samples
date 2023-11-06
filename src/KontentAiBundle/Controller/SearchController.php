<?php

namespace KontentAiBundle\Controller;

use CoreBundle\Exception\InvalidPayloadException;
use CoreBundle\ViewModel\JsonResponseViewModel;
use CoreBundle\ViewModel\PagedResultViewModel;
use CoreBundle\Controller\AbstractController;
use ElasticSearchBundle\Model\ElasticSearchPageModel;
use ElasticSearchBundle\SearchRepository\PageSearchRepository;
use ElasticSearchBundle\Service\PagesIndexManuallyUpdate;
use KontentAiBundle\Service\PageService;
use KontentAiBundle\ViewModel\PageViewModel;
use Kontent\Ai\Delivery\Models\Items\ContentItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Elastica\Result;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SearchController extends AbstractController {
    protected const KONTENT_AI_DATA = 'data';
    protected const KONTENT_AI_ITEMS = 'items';
    protected const KONTENT_AI_SECRET_HEADER = 'x-kc-signature';

    protected PageSearchRepository $searchRepository;
    protected PageService $apiPageService;
    protected PagesIndexManuallyUpdate $esPageService;
    protected string $webhookSecret;

    public function __construct(
        PageSearchRepository $searchRepository,
        PageService $apiPageService,
        PagesIndexManuallyUpdate $esPageService,
        string $webhookSecret
    ) {
        $this->searchRepository = $searchRepository;
        $this->apiPageService = $apiPageService;
        $this->esPageService = $esPageService;
        $this->webhookSecret = $webhookSecret;
    }

    public function findPagesAction(Request $request): JsonResponse {
        $jsonResponseViewModel = (new JsonResponseViewModel())->setStatus(400)->setMessage('not handled payload');

        $locale = $request->getLocale();
        /** @var int $offset */
        $offset = $this->getUrlParam('offset', self::URL_PARAM_TYPE_INT, 0);
        /** @var int $limit */
        $limit = $this->getUrlParam('limit', self::URL_PARAM_TYPE_INT, 1000);
        /** @var string $channel */
        $channel = $this->getUrlParam('channel', self::URL_PARAM_TYPE_STRING, '');
        /** @var string $query */
        $query = $this->getUrlParam('search', self::URL_PARAM_TYPE_STRING, '');
        $query = trim($query);

        [$data, $count] = $this->searchRepository->searchForPage($query, $offset, $limit, $locale, $channel);

        /** @var Result $elasticObject */
        $viewModels = array_map(function ($elasticObject) {
            return new PageViewModel($elasticObject);
        }, $data);

        $viewModel = PagedResultViewModel::createFromComponents($count, $offset, $limit, $viewModels);
        $jsonResponseViewModel->setData($viewModel)->setMessage('OK')->setStatus(200);

        return new JsonResponse($jsonResponseViewModel, $jsonResponseViewModel->getStatus());
    }

    private function checkWebhookAccess(Request $request): bool {
        $givenSignature = $request->headers->get(self::KONTENT_AI_SECRET_HEADER);
        if (!is_string($givenSignature)) {
            return false;
        }

        $computedSignature = base64_encode(
            hash_hmac('sha256', (string)$request->getContent(), $this->webhookSecret, true)
        );

        return hash_equals($givenSignature, $computedSignature);
    }

    /**
     * @throws InvalidPayloadException
     */
    public function updatePagesAction(Request $request) :JsonResponse {
        if (!$this->checkWebhookAccess($request)) {
            throw new AccessDeniedHttpException('Not allowed to perform this action!');
        }

        $payload = json_decode((string)$request->getContent(), true);
        if (
            !$payload
            || !array_key_exists(self::KONTENT_AI_DATA, $payload)
            || !array_key_exists(self::KONTENT_AI_ITEMS, $payload[self::KONTENT_AI_DATA])
        ) {
            throw new InvalidPayloadException();
        }

        $ids = [];

        foreach ($payload[self::KONTENT_AI_DATA][self::KONTENT_AI_ITEMS] as $item) {
            $ids[] = $item['id'];
        }

        $itemsToUpdate = $this->apiPageService->getKontentAiUpdatedPagesOnly($ids);

        $pageModels = [];
        $updatedIds = [];

        foreach ($itemsToUpdate as $page) {
            $pageModel = new ElasticSearchPageModel($page);
            $pageModels[] = $pageModel;
            $updatedIds[] = $pageModel->getId();
        }

        if (count($pageModels)) {
            $this->esPageService->update($pageModels);
        }

        /** statement will be true if the ID(s) of page(s) received from the webhook were unpublished or deleted */
        if ($idsToDelete = array_diff($ids, $updatedIds)) {
            $pagesToDelete = null;

            foreach ($idsToDelete as $id) {
                $pagesToDelete[] = new ElasticSearchPageModel(new ContentItem(), $id);
            }

            $this->esPageService->delete($pagesToDelete);
        }

        return new JsonResponse('Data successfully updated', 200);
    }
}