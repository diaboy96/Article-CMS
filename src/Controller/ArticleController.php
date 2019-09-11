<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleType;
use App\Form\CommentType;
use HTMLPurifier;
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
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createArticle(Request $request)
    {
        $admin_is_logged_in = new AdminController();
        $admin_is_logged_in->checkIfAdminIsLoggedIn();

        if ($admin_is_logged_in) {
            // make form
            $article_form = $this->createForm(ArticleType::class);
            $article_form->handleRequest($request);

            if ($article_form->isSubmitted() && $article_form->isValid()) {
                // get data and filter from xss
                $entityManager = $this->getDoctrine()->getManager();
                $data = $article_form->getData();
                $data = $this->purifyFormData($data);

                $article = new Article();
                $article->setHeader($data['article_header']);
                $article->setContent($data['article_content']);
                $entityManager->persist($article);
                $entityManager->flush();

                $url = $this->generateUrl('admin', ['message' => 'Článek byl úspěšně vytvořen', 'type_message' => 'success']);
                return $this->redirect($url.'#message');
            }

            return $this->render('admin/article_manage.html.twig', [
                'article_form' => $article_form->createView()
            ]);
        } else {
            return $this->redirectToRoute('admin');
        }
    }

    /**
     * @Route("/editArticle/{article_id}", name="article_edit", defaults={"article_id" = "not_set"})
     * @param $article_id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editArticle($article_id, Request $request)
    {
        $admin_is_logged_in = new AdminController();
        $admin_is_logged_in->checkIfAdminIsLoggedIn();

        if ($admin_is_logged_in) {

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

                // make form
                $article_form = $this->createForm(ArticleType::class);
                $article_form->handleRequest($request);

                if ($article_form->isSubmitted() && $article_form->isValid()) {
                    // get data and filter from xss
                    $entityManager = $this->getDoctrine()->getManager();
                    $data = $article_form->getData();

                    $data = $this->purifyFormData($data);

                    $article->setHeader($data['article_header']);
                    $article->setContent($data['article_content']);
                    $entityManager->flush();

                    $url = $this->generateUrl('admin', ['message' => 'Článek byl úspěšně upraven', 'type_message' => 'success']);
                    return $this->redirect($url.'#message');
                }

                return $this->render('admin/article_manage.html.twig', [
                    'article_form' => $article_form->createView(),
                    'article_content' => $article_content,
                    'article_header' => $article_header
                ]);
            } else {
                $url = $this->generateUrl('admin', ['message' => 'Článek nebyl nalezen v databázi', 'message_type' => 'error']);
                return $this->redirect($url.'#message');
            }

        } else {
            return $this->redirectToRoute('admin');
        }
    }

    /**
     * @Route("/removeArticle/{article_id}", name="article_remove", defaults={"article_id" = "not_set"})
     * @param $article_id
     * @return RedirectResponse
     */
    public function removeArticle($article_id)
    {
        $admin_is_logged_in = new AdminController();
        $admin_is_logged_in->checkIfAdminIsLoggedIn();

        if ($admin_is_logged_in) {

            $doctrine = $this->getDoctrine();
            $entityManager = $doctrine->getManager();
            $article_id = intval($article_id);
            $article = $doctrine
                ->getRepository(Article::class)
                ->findOneBy([
                    'id' => $article_id
                ]);

            if ($article) {
                $entityManager->remove($article);
                $entityManager->flush();

                $url = $this->generateUrl('admin', ['message' => 'Článek byl úspěšně vymazán', 'message_type' => 'success']);
                return $this->redirect($url."#message");
            } else {
                $url = $this->generateUrl('admin', ['message' => 'Článek nebyl nalezen v databázi', 'message_type' => 'error']);
                return $this->redirect($url.'#message');
            }
        } else {
            return $this->redirectToRoute('admin');
        }
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

                if ($user_id > 0) { // user is logged in
                    // comment form (for creating new comment)
                    $comment_form = $this->createForm(CommentType::class);
                    $comment_form->handleRequest($request);

                    if ($comment_form->isSubmitted() && $comment_form->isValid()) {
                        $form_data = $comment_form->getData();
                        $MainController = new MainController();
                        $saved = $MainController->processSaveComment($doctrine, $user_id, $form_data);
                        if ($saved === true) {
                            $message = 'Komentář byl úspěšně vytvořen.';
                            $message_type = 'success';
                        } else {
                            $message = $saved;
                            $message_type = 'error';
                        }
                        $url = $this->generateUrl('article_detail');
                        return $this->redirect($url . '/' . $article_id . '?message=' . $message . '&message_type='.$message_type.'#message');
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

                } else { // user is not logged in
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


        $url = $this->generateUrl('main', [
            'message' => $message,
            'message_type' => 'error'
        ]);

        return $this->redirect($url.'#message');
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
        $admin_is_logged_in = new AdminController();
        $admin_is_logged_in->checkIfAdminIsLoggedIn();

        if ($comment['is_owned_by_user'] || $admin_is_logged_in) {
            $entityManager =  $this->getDoctrine()->getManager();

            $comment['comment']->setComment($request->query->get('edit_comment_value'));
            $entityManager->persist($comment['comment']);
            $entityManager->flush();

            $message = 'Komentář byl úspěšně upraven.';
            $message_type = 'success';
        } else {
            $message = $comment['message'];
            $message_type = 'error';
        }

        if ($admin_is_logged_in) {
            $route = 'admin';
        } else {
            $route = 'main';
        }

        $url = $this->generateUrl($route, [
            'message' => $message,
            'message_type' => $message_type
        ]);

        return $this->redirect($url.'#message');
    }

    /**
     * @Route("/removeComment/{comment_id}", name="comment_remove", defaults={"comment_id" = "not_set"})
     * @param $comment_id
     * @return RedirectResponse
     */
    public function removeComment($comment_id)
    {
        $comment = $this->checkIfCommentIsOwnedByCurrentlyLoggedUser($comment_id);
        $admin_is_logged_in = new AdminController();
        $admin_is_logged_in->checkIfAdminIsLoggedIn();

        if ($comment['is_owned_by_user'] || $admin_is_logged_in) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment['comment']);
            $entityManager->flush();

            $message = 'Komentář byl úspěšně vymazán.';
            $message_type = 'success';
        } else {
            $message = $comment['message'];
            $message_type = 'error';
        }

        if ($admin_is_logged_in) {
            $route = 'admin';
        } else {
            $route = 'main';
        }

        $url = $this->generateUrl($route, [
            'message' => $message,
            'message_type' => $message_type
        ]);

        return $this->redirect($url.'#message');
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

    /**
     * @param $data
     * @return mixed|string|string[]|null
     */
    protected function cleanForXSS($data)
    {
// Fix &entity\n;
        $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
        $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
        $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
        $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

// Remove any attribute starting with "on" or xmlns
        $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

// Remove javascript: and vbscript: protocols
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
        $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
        $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

// Remove namespaced elements (we do not need them)
        $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

        do {
            // Remove really unwanted tags
            $old_data = $data;
            $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
        } while ($old_data !== $data);

// we are done...
        return $data;
    }

    /**
     * @param $data
     * @return array
     */
    private function purifyFormData($data)
    {
        $htmlPurifier = new HTMLPurifier();

        $article_header = htmlspecialchars(strip_tags($data->getHeader()));
        $article_header = $this->cleanForXSS($article_header);
        $article_header = $htmlPurifier->purify($article_header);

        $article_content = $this->cleanForXSS($data->getContent());
        $article_content = $htmlPurifier->purify($article_content);

        return [
            'article_header' => $article_header,
            'article_content' => $article_content
        ];
    }
}