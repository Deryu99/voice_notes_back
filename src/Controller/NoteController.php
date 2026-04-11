<?php

namespace App\Controller;

use App\Entity\Note;
use App\Service\NoteService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NoteController extends AbstractController
{
    public function __construct(
        private readonly NoteService $noteService) {}

    #[Route('/note', name: 'app_note')]
    public function index(): Response
    {
        return $this->render('note/index.html.twig', [
            'controller_name' => 'NoteController',
        ]);
    }

    #[Route('/import-notes', name: 'import_notes', methods: ['POST'])]
    public function import(): JsonResponse
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projectDir) || $projectDir === '') {
            return $this->json([
                'success' => false,
                'message' => 'Project directory not found.',
            ],
                Response::HTTP_NOT_FOUND);
        }

        $notesFile = $projectDir.'/data/notes.json';
        $result = $this->noteService->importFromJsonFile($notesFile);

        if ($result['success'] === false) {
            $statusCode = $result['imported'] === 0 ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK;

            return $this->json($result, $statusCode);
        }

        return $this->json($result, Response::HTTP_OK);
    }

    #[Route('/get-notes', name: 'get_notes')]
    public function getNotes(): JsonResponse {
        $notes = $this->noteService->findAll();
        $payload = array_map(fn (Note $note) => $this->noteService->toArray($note), $notes);

        return $this->json($payload, Response::HTTP_OK);
    }

    #[Route('/search-notes', name: 'search_notes')]
    public function searchNotes(Request $request): JsonResponse {
        $query = $request->query->get('q');
        $results = $this->noteService->findByText($query);
        return $this->json($results, Response::HTTP_OK);
    }

    #[Route('/update-note', name: 'update_note', methods: ['POST'])]
    public function saveNote(Request $request): JsonResponse {
        $payload = $this->parseJsonBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $id = $this->extractValidId($payload, true);
        if ($id instanceof JsonResponse) {
            return $id;
        }

        $fields = $this->extractUpdateFields($payload);
        if ($fields instanceof JsonResponse) {
            return $fields;
        }

        $updatedNote = $this->noteService->update($id, $fields);
        if (!$updatedNote instanceof Note) {
            return $this->jsonError('Note not found', Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->noteService->toArray($updatedNote), Response::HTTP_OK);
    }

    #[Route('/delete-note', name: 'delete_note', methods: ['POST'])]
    public function deleteNote(Request $request): JsonResponse {
        $payload = $this->parseJsonBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $id = $this->extractValidId($payload);
        if ($id instanceof JsonResponse) {
            return $id;
        }

        $deleted = $this->noteService->deleteById($id, true);
        if ($deleted === false) {
            return $this->jsonError('Note not found', Response::HTTP_NOT_FOUND);
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }

    private function parseJsonBody(Request $request): array|JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->jsonError('Invalid JSON body', Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($data)) {
            return $this->jsonError('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        return $data;
    }

    private function extractValidId(array $payload, bool $allowMissingMessage = false): int|JsonResponse
    {
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        if ($id <= 0) {
            if ($allowMissingMessage) {
                return $this->jsonError('Missing or invalid id', Response::HTTP_BAD_REQUEST);
            }

            return $this->jsonError('Invalid id', Response::HTTP_BAD_REQUEST);
        }

        return $id;
    }

    private function extractUpdateFields(array $payload): array|JsonResponse
    {
        $fields = [];

        if (array_key_exists('title', $payload)) {
            if (!is_string($payload['title']) || trim($payload['title']) === '') {
                return $this->jsonError('Invalid title', Response::HTTP_BAD_REQUEST);
            }

            $fields['title'] = trim($payload['title']);
        }

        if (array_key_exists('summary', $payload)) {
            if (!is_string($payload['summary'])) {
                return $this->jsonError('Invalid summary', Response::HTTP_BAD_REQUEST);
            }

            $fields['summary'] = $payload['summary'];
        }

        if ($fields === []) {
            return $this->jsonError('No fields to update', Response::HTTP_BAD_REQUEST);
        }

        return $fields;
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return $this->json(['success' => false, 'error' => $message], $status);
    }
}
