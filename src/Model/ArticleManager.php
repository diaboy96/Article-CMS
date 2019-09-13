<?php


namespace App\Model;


use App\Entity\Article;
use App\Entity\Comment;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;

class ArticleManager
{
    /**
     * @param ManagerRegistry $doctrine
     * @return array
     */
    public function getAllArticlesWithComments(ManagerRegistry $doctrine)
    {
        //get article repository
        $articles = $doctrine
            ->getRepository(Article::class)
            ->createQueryBuilder('a')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        // get comment repository
        $comments = $doctrine
            ->getRepository(Comment::class)
            ->fetchAllCommentsAndJoinUserName();

        return [
            'articles' => $articles,
            'comments' => $comments
        ];
    }
}