<?php

namespace App\Repository;

use App\Entity\Speaker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Speaker>
 */
class SpeakerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Speaker::class);
    }

    public function getSpeakerPodium()
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.distance', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getArrayResult();
    }
}
