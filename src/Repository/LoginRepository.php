<?php

namespace App\Repository;

use App\Entity\Login;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Result;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception;

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

    /**
     * @throws Exception
     */
    public function fetchAllDataExceptPasswords(): Result
    {
        $conn = $this->getEntityManager()
            ->getConnection();
        $sql = '
SELECT `id`, `name`, `email`, `hash`, `active`, `type`
FROM `login`';
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery();
    }
}
