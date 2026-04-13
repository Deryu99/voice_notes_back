<?php

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    //    /**
    //     * @return Note[] Returns an array of Note objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Note
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function searchByText(string $query): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.title LIKE :q')
            ->orWhere('n.summary LIKE :q')
            ->orWhere('n.tags LIKE :q')
            ->orderBy('n.createdAt', 'DESC')
            ->setParameter('q', '%' . $query . '%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function deleteTagFromNote(int $id, string $tag): ?Note
    {
        $note = $this->find($id);
        if (!$note instanceof Note) {
            return null;
        }

        $tags = $note->getTags() ?? [];

        // Remove the tag from the array
        $updatedTags = array_filter(
            $tags, fn($currentTag) => $currentTag !== $tag
        );

        // Re-index array to maintain consistency
        $note->setTags(array_values($updatedTags));

        $this->getEntityManager()->flush();

        return $note;
    }

    public function save(Note $note, bool $flush = false): void
    {
        $this->getEntityManager()->persist($note);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function delete(Note $note, bool $flush = false): void
    {
        $this->getEntityManager()->remove($note);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
