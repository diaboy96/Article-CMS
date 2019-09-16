<?php

namespace App\Repository;

use App\Entity\Login;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Login|null find($id, $lockMode = null, $lockVersion = null)
 * @method Login|null findOneBy(array $criteria, array $orderBy = null)
 * @method Login[]    findAll()
 * @method Login[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Login::class);
    }

    public function fetchAllDataExceptPasswords()
    {
        $conn = $this->getEntityManager()
            ->getConnection();
        $sql = '
SELECT `id`, `name`, `email`, `hash`, `active`, `type`
FROM `login`';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
    // /**
    //  * @return LoginManager[] Returns an array of LoginManager objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LoginManager
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
