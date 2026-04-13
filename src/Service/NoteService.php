<?php

namespace App\Service;

use App\Entity\Note;
use App\Repository\NoteRepository;
use DateTimeImmutable;
use JsonException;

class NoteService
{
    public function __construct(private readonly NoteRepository $noteRepository) {}

    public function save(Note $note, bool $flush = false): void
    {
        $this->noteRepository->save($note, $flush);
    }

    public function importFromJsonFile(string $notePath): array
    {
        $result = $this->newImportResult();
        $fileContents = $this->readJsonFile($notePath);
        if ($fileContents === null) {
            $result['errors'][] = 'Could not read the JSON file.';

            return $result;
        }

        $notes = $this->decodeNotes($fileContents);
        if ($notes === null) {
            $result['errors'][] = 'The file does not contain valid JSON.';

            return $result;
        }

        if (!is_array($notes)) {
            $result['errors'][] = 'The JSON content must be an array of notes.';

            return $result;
        }

        foreach ($notes as $index => $rawNote) {
            if (!is_array($rawNote)) {
                $result['skipped']++;
                $result['errors'][] = sprintf('Note #%d skipped: invalid format.', $index + 1);
                continue;
            }

            $note = $this->buildNoteEntity($rawNote);
            if ($note === null) {
                $result['skipped']++;
                $result['errors'][] = sprintf('Note #%d skipped: missing required fields or invalid date.', $index + 1);
                continue;
            }

            $this->save($note, false);
            $result['imported']++;
        }

        if ($result['imported'] > 0) {
            $this->noteRepository->flush();
            $result['success'] = true;
        }

        return $result;
    }

    private function buildNoteEntity(array $rawNote): ?Note
    {
        $title = $rawNote['title'] ?? null;
        $createdAt = $rawNote['createdAt'] ?? null;

        if (!is_string($title) || $title === '' || !is_string($createdAt)) {
            return null;
        }

        try {
            $createdAtDate = new DateTimeImmutable($createdAt);
        } catch (\Exception) {
            return null;
        }

        $note = new Note();
        $note->setTitle($title);
        $note->setCreatedAt($createdAtDate);

        if (isset($rawNote['summary']) && is_string($rawNote['summary'])) {
            $note->setSummary($rawNote['summary']);
        }

        if (isset($rawNote['tags']) && is_array($rawNote['tags'])) {
            $note->setTags($rawNote['tags']);
        }

        return $note;
    }

    private function newImportResult(): array
    {
        return [
            'success' => false,
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
    }

    private function readJsonFile(string $notePath): ?string
    {
        $file = file_get_contents($notePath);

        return $file === false ? null : $file;
    }

    private function decodeNotes(string $fileContents): mixed
    {
        try {
            return json_decode($fileContents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }

    public function delete(Note $note, bool $flush = false): void
    {
        $this->noteRepository->delete($note, $flush);
    }

    public function deleteById(int $noteId, bool $flush = false): bool
    {
        $noteToDelete = $this->noteRepository->find($noteId);
        if (!$noteToDelete instanceof Note) {
            return false;
        }

        $this->noteRepository->delete($noteToDelete, $flush);

        return true;
    }

    public function findAll(): array
    {
        return $this->noteRepository->findAll();
    }

    public function update(int $id, array $fields): ?Note
    {
        $note = $this->noteRepository->find($id);
        if (!$note instanceof Note) {
            return null;
        }

        if (array_key_exists('title', $fields) && is_string($fields['title'])) {
            $note->setTitle($fields['title']);
        }

        if (array_key_exists('summary', $fields) && (is_string($fields['summary']) || $fields['summary'] === null)) {
            $note->setSummary($fields['summary']);
        }

        $this->noteRepository->flush();
        return $note;
    }

    public function findByText(string $query): array
    {
        return $this->noteRepository->searchByText($query);
    }

    public function deleteTagFromNote(int $id, string $tag): ?Note
    {
        return $this->noteRepository->deleteTagFromNote($id, $tag);
    }

    public function toArray(Note $note): array
    {
        return [
            'id' => $note->getId(),
            'title' => $note->getTitle() ?? '',
            'summary' => $note->getSummary() ?? '',
            'tags' => $note->getTags() ?? [],
            'createdAt' => $note->getCreatedAt()?->format(DATE_ATOM) ?? '',
        ];
    }
}
