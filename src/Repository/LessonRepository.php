<?php

namespace App\Repository;

use App\Entity\Lesson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lesson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function save(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAll(): array
    {
        return $this->findBy(array(), array('serialNumber' => 'ASC'));
    }

    public function checkSerialNumber(Lesson $entity) : void
    {
        $course = $entity->getCourse();
        $lessons = $course->getLessons();
        $serialNumber = $entity->getSerialNumber();
        $highestNumber = 0;
        foreach ($lessons as $lesson) {
            if ($serialNumber <= $lesson->getSerialNumber() && $entity !== $lesson) {
                $lesson->setSerialNumber($lesson->getSerialNumber() + 1);
            }
            if ($highestNumber < $lesson->getSerialNumber() && $entity !== $lesson) {
                $highestNumber = $lesson->getSerialNumber();
            }
        }
        if ($serialNumber < 1) {
            $serialNumber = 1;
        } elseif ($serialNumber > $highestNumber) {
            $serialNumber = $highestNumber + 1;
        }
        $entity->setSerialNumber($serialNumber);
    }

    public function moveSerialNumbers(Lesson $entity, int $oldSerialNumber) : void
    {
        $course = $entity->getCourse();
        $lessons = $course->getLessons();
        foreach ($lessons as $lesson) {
            if ($oldSerialNumber <= $lesson->getSerialNumber() && $entity !== $lesson) {
                $lesson->setSerialNumber($lesson->getSerialNumber() - 1);
            }
        }
    }

//    /**
//     * @return Lesson[] Returns an array of Lesson objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Lesson
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
