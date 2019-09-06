<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    /**
     * @Route("/createArticle", name="article_create")
     */
    public function createArticle()
    {
        //todo admin
    }

    /**
     * @Route("/editArticle/{article_id}", name="article_edit", defaults={"article_id" = "not_set"})
     */
    public function editArticle($article_id)
    {
        //todo admin
    }

    /**
     * @Route("/removeArticle/{article_id}", name="article_remove", defaults={"article_id" = "not_set"})
     * @param $article_id
     */
    public function removeArticle($article_id)
    {
        //todo admin
    }

    /**
     * @Route("/articleDetail/{article_id}", name="article_detail", defaults={"article_id" = "not_set"})
     * @param $article_id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function articleDetail($article_id, Request $request)
    {
        if ($article_id !== 'not_set') {
            $doctrine = $this->getDoctrine();
            $article_id = intval($article_id);
            $article = $doctrine
                ->getRepository(Article::class)
                ->findOneBy([
                    'id' => $article_id
                ]);

            if ($article) {
                $article_header = $article->getHeader();
                $article_content = $article->getContent();
                $comments = $doctrine
                    ->getRepository(Comment::class)
                    ->fetchCommentsByArticleIdAndJoinUserName($article_id);

                $session = new Session();
                $user_id = $session->get('user_id');
                $user_name = $session->get('user_name');

                if ($user_id > 0) {
                    // comment form (for creating new comment)
                    $comment_form = $this->createForm(CommentType::class);
                    $comment_form->handleRequest($request);

                    if ($comment_form->isSubmitted() && $comment_form->isValid()) {
                        $form_data = $comment_form->getData();
                        $MainController = new MainController();
                        $saved = $MainController->processSaveComment($doctrine, $user_id, $form_data);
                        if ($saved === true) {
                            $message = 'Komentář byl úspěšně vytvořen.';
                        } else {
                            $message = $saved;
                        }
                        $url = $this->generateUrl('article_detail');
                        return $this->redirect($url . '/' . $article_id . '?message=' . $message);
                    }

                    return $this->render('article/article_detail.html.twig', [
                        'article_id' => $article_id,
                        'article_header' => $article_header,
                        'article_content' => $article_content,
                        'comments' => $comments,
                        'user_id' => $user_id,
                        'user_name' => $user_name,
                        'button_back' => true,
                        'comment_form' => $comment_form->createView()
                    ]);

                } else {
                    return $this->render('article/article_detail.html.twig', [
                        'article_id' => $article_id,
                        'article_header' => $article_header,
                        'article_content' => $article_content,
                        'comments' => $comments,
                        'button_back' => true
                    ]);
                }
            } else {
                $message = "Článek nebyl nalezen v databázi.";
            }
        } else {
            $message = "V URL adrese se nenachází id článku";
        }

        return $this->redirectToRoute('main', ['message' => $message]);
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
