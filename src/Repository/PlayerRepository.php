<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 *
 * @method Player|null find($id, $lockMode = null, $lockVersion = null)
 * @method Player|null findOneBy(array $criteria, array $orderBy = null)
 * @method Player[]    findAll()
 * @method Player[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }


    public function findByPosition(string $position): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.position = :position')
            ->setParameter('position', $position)
            ->orderBy('p.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findByTeam(string $team): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.team = :team')
            ->setParameter('team', $team)
            ->orderBy('p.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findByAgeRange(int $minAge, int $maxAge): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.age >= :minAge')
            ->andWhere('p.age <= :maxAge')
            ->setParameter('minAge', $minAge)
            ->setParameter('maxAge', $maxAge)
            ->orderBy('p.age', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
