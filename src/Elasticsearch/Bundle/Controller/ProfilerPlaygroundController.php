<?php

declare(strict_types=1);

namespace Elasticsearch\Bundle\Controller;

use Elasticsearch\Bundle\Profiler\PlaygroundService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ProfilerPlaygroundController
{
    public function __construct(
        private PlaygroundService $playgroundService,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private bool $kernelDebug,
    ) {
    }

    public function generate(Request $request): JsonResponse
    {
        return $this->handle($request, false);
    }

    public function execute(Request $request): JsonResponse
    {
        return $this->handle($request, true);
    }

    private function handle(Request $request, bool $execute): JsonResponse
    {
        if (!$this->kernelDebug) {
            return new JsonResponse(['error' => 'Not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $token = (string) $request->request->get('_token', '');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('elasticsearch_playground', $token))) {
            return new JsonResponse(['error' => 'Invalid CSRF token.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $query = trim((string) $request->request->get('query', ''));
        $index = $request->request->get('index');
        $operation = trim((string) $request->request->get('operation', 'search'));

        if ('' === $query) {
            return new JsonResponse(['error' => 'Query is required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $data = $execute
                ? $this->playgroundService->execute($query, is_string($index) ? $index : null, $operation)
                : $this->playgroundService->generatePhp($query);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($data);
    }
}
