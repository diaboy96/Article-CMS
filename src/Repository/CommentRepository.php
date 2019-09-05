<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function fetchAllCommentsAndJoinUserName()
    {
        $conn = $this->getEntityManager()
            ->getConnection();
        $sql = '
SELECT article_id, com.id as id, user_id, comment, name as user_name
FROM `comment` com
INNER JOIN `login` log
ON com.user_id = log.id
ORDER BY com.id DESC';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function fetchCommentsByArticleIdAndJoinUserName($article_id)
    {
        $conn = $this->getEntityManager()
            ->getConnection();
        $sql = '
SELECT com.id as id, article_id, user_id, comment, name as user_name
FROM `comment` com
INNER JOIN `login` log
ON com.user_id = log.id
WHERE article_id = '.$article_id.'
ORDER BY com.id DESC';
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
    // /**
    //  * @return Comment[] Returns an array of Comment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Comment
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
