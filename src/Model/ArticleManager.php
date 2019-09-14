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

    /**
     * @param ManagerRegistry $doctrine
     * @param $user_id
     * @param $form_data
     * @return bool|string
     */
    public function processSaveComment(ManagerRegistry $doctrine, $user_id, $form_data)
    {
        $comment_value = htmlspecialchars(strip_tags($form_data->getComment()));
        $article_id = intval($form_data->getArticleId());

        //save comment to db
        if (!empty($comment_value) && !empty($article_id)) {
            $entityManager = $doctrine->getManager();
            $comment = new Comment();
            $comment->setArticleId($article_id);
            $comment->setUserId($user_id);
            $comment->setComment($comment_value);

            $entityManager->persist($comment);
            $entityManager->flush();
        } else {
            $message = "Zadejte hodnotu komentáře";

            return $message;
        }
        return true;
    }
}