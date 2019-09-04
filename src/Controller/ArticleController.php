<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    /**
     * @Route("/createArticle", name="article_create")
     */
    public function createArticle()
    {
        //todo
    }

    /**
     * @Route("/editArticle/{article_id}", name="article_edit", defaults={"article_id" = "not_set"})
     */
    public function editArticle($article_id)
    {
        //todo
    }

    /**
     * @Route("/removeArticle/{article_id}", name="article_remove", defaults={"article_id" = "not_set"})
     */
    public function removeArticle($article_id)
    {
        //todo
    }
    
    /**
     * @Route("/articleDetail/{article_id}", name="article_detail", defaults={"article_id" = "not_set"})
     */
    public function articleDetail($article_id)
    {
        if ($article_id !== 'not_set') {
            $article_id = intval($article_id);
        }
        
        return $this->render('article/article_detail.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    /**
     * @Route("/editComment/{comment_id}", name="comment_edit", defaults={"comment_id" = "not_set"})
     */
    public function editComment($comment_id)
    {
        dump('edit', $comment_id);
        // TODO: make ajax form input instead of comment text
        //todo HERE validate data and save it to db (try using ajax)
    }

    /**
     * @Route("/removeComment/{comment_id}", name="comment_remove", defaults={"comment_id" = "not_set"})
     */
    public function removeComment($comment_id)
    {
        // TODO!!!
        dump('remove', $comment_id);
    }
}
