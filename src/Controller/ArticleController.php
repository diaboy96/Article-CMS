<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    /**
     * @Route("/create/{article_id}", name="article_create")
     */
    public function createArticle()
    {
        //todo
    }

    /**
     * @Route("/edit/{article_id}", name="article_edit")
     */
    public function editArticle()
    {
        //todo
    }

    /**
     * @Route("/remove/{article_id}", name="article_remove")
     */
    public function removeArticle()
    {
        //todo
    }
    
    /**
     * @Route("/article/{article_id}", name="article_detail", defaults={"article_id" = "not_set"})
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
}
