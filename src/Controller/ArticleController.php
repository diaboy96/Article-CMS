<?php

namespace App\Controller;

use App\Entity\Comment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
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
        // todo
        
        return $this->render('article/article_detail.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    /**
     * @Route("/editComment/{comment_id}", name="comment_edit", defaults={"comment_id" = "not_set"})
     * @param $comment_id
     * @param Request $request
     * @return RedirectResponse
     */
    public function editComment($comment_id, Request $request)
    {
        $comment = $this->checkIfCommentIsOwnedByCurrentlyLoggedUser($comment_id);

        if ($comment['is_owned_by_user']) {
            $entityManager =  $this->getDoctrine()->getManager();

            $comment['comment']->setComment($request->query->get('edit_comment_value'));
            $entityManager->persist($comment['comment']);
            $entityManager->flush();

            $message = 'Komentář byl úspěšně upraven.';
            $message_type = 'success';
        } else {
            $message = $comment['message'];
            $message_type = 'failure';
        }

        $url = $this->generateUrl('main', [
            'message' => $message,
            'message_type' => $message_type
        ]);

        return $this->redirect($url); // todo use get parameters in URL to display remodal (or message) of performed action
    }

    /**
     * @Route("/removeComment/{comment_id}", name="comment_remove", defaults={"comment_id" = "not_set"})
     * @param $comment_id
     * @return RedirectResponse
     */
    public function removeComment($comment_id)
    {
        $comment = $this->checkIfCommentIsOwnedByCurrentlyLoggedUser($comment_id);

        if ($comment['is_owned_by_user']) {
            $entityManager =  $this->getDoctrine()->getManager();
            $entityManager->remove($comment['comment']);
            $entityManager->flush();

            $message = 'Komentář byl úspěšně vymazán.';
            $message_type = 'success';
        } else {
            $message = $comment['message'];
            $message_type = 'failure';
        }

        $url = $this->generateUrl('main', [
            'message' => $message,
            'message_type' => $message_type
        ]);

        return $this->redirect($url); // todo use get parameters in URL to display remodal (or message) of performed action
    }

    /**
     * @param $comment_id
     * @return array
     */
    protected function checkIfCommentIsOwnedByCurrentlyLoggedUser($comment_id)
    {

        $session = new Session();
        $user_id = $session->get('user_id');
        $doctrine = $this->getDoctrine();

        $comment = $doctrine
            ->getRepository(Comment::class)
            ->findOneBy([
                'id' => intval($comment_id),
                'user_id' => $user_id
            ]);

        if ($comment) { // comment is owned by currently user
            return ['is_owned_by_user' => true, 'comment' => $comment];
        } else {
            $comment = $doctrine
                ->getRepository(Comment::class)
                ->findOneBy([
                    'id' => intval($comment_id)
                ]);

            if ($comment) { // comment exist but is not owned by currently logged user
                $message = "Nelze pracovat s komentářem, jekož autorem je jiný uživatel.";

                return ['is_owned_by_user' => false, 'message' => $message];
            } else { // comment was not found in database
                $message = "Komentář nebyl nalezen v databázi.";

                return ['is_owned_by_user' => false, 'message' => $message];
            }
        }
    }
}
