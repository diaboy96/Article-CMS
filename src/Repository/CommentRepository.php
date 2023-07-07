<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

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

	/**
	 * @throws Exception
	 */
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

        return $stmt->executeQuery();
    }

	/**
	 * @throws Exception
	 */
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

        return $stmt->executeQuery();
    }

	/**
	 * @throws Exception
	 */
	public function fetchCommentsByUserId($user_id)
    {
        $conn = $this->getEntityManager()
            ->getConnection();
        $sql = '
SELECT *
FROM `comment`
WHERE user_id = '.$user_id;
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery();
    }
}
