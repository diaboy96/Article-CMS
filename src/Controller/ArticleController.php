<?php

namespace App\Controller;

use App\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    public function fetchAllArticles()
    {
        $articles = $this->getDoctrine()->getRepository(Article::class)->findBy(['id' => 'DESC']);

        return $articles;
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
