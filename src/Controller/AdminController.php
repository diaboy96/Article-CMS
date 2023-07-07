<?php

namespace App\Controller;

use App\Form\CommentType;
use App\Form\LoginType;
use App\Model\ArticleManager;
use App\Model\LoginManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function index(Request $request)
    {
        $session = $request->getSession();
        $doctrine = $this->getDoctrine();
        $articleManager = new ArticleManager();
        $articles_and_comments = $articleManager->getAllArticlesWithComments($doctrine);

        $admin_is_logged_in = $this->checkIfAdminIsLoggedIn();

        if ($admin_is_logged_in) {
            $admin_id = $session->get('admin_id');
            $admin_name = $session->get('admin_name');

            // generate CommentType forms
            $comment_forms = [];
            foreach ($articles_and_comments['articles'] as $article) {
                $comment_form = $this->createForm(CommentType::class)->createView();
                $comment_forms[$article['id']] = $comment_form;
            }

            // handle CommentType request
            $comment_form = $this->createForm(CommentType::class);
            $comment_form->handleRequest($request);

            if ($comment_form->isSubmitted() && $comment_form->isValid()) { // handle COMMENT form
                $form_data = $comment_form->getData();
                $saved = $articleManager->processSaveComment($doctrine, $admin_id, $form_data);

                if ($saved === true) {
                    $message_type = 'success';
                    $message = 'Komentář byl úspěšně uložen';
                } else {
                    $message_type = 'error';
                    $message = $saved;
                }

                $url = $this->generateUrl('admin', [
                    'message' => $message,
                    'message_type' => $message_type
                ]);

                return $this->redirect($url.'#message');
            }

            return $this->render('admin/index.html.twig', [
                'articles' => $articles_and_comments['articles'],
                'comments' => $articles_and_comments['comments'],
                'comment_forms' => $comment_forms,
                'admin_id' => $admin_id,
                'admin_name' => $admin_name
            ]);

        } else {
            $login_form = $this->createForm(LoginType::class);
            $login_form->handleRequest($request);
            if ($login_form->isSubmitted() && $login_form->isValid()) { // handle LOGIN form
                $form_data = $login_form->getData();
                $name = htmlspecialchars(strip_tags($form_data->getName()));
                $pass = hash('sha512', htmlspecialchars(strip_tags($form_data->getPass())));

                $loginManager = new LoginManager();
                $login = $loginManager->processLogin($doctrine, $name, $pass, 'admin');
                if ($login['logged'] === true) {
                    $session->set('admin_id', $login['admin_id']);
                    $session->set('admin_name', $login['admin_name']);

                    return $this->redirectToRoute('admin');
                } elseif ($login['logged'] === false) {
                    $message_type = 'error';
                    $message = $login['message'];

                    $url = $this->generateUrl('admin', [
                        'message' => $message,
                        'message_type' => $message_type
                    ]);

                    return $this->redirect($url.'#message');
                }
            }

            return $this->render('admin/index.html.twig', [
                'articles' => $articles_and_comments['articles'],
                'comments' => $articles_and_comments['comments'],
                'login_form' => $login_form->createView()
            ]);
        }
    }

    /**
     * @return bool
     */
    public function checkIfAdminIsLoggedIn(Request $request)
    {
        $session = $request->getSession();
        $admin_id = intval($session->get('admin_id'));

        if ($admin_id > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @Route("/admin/logout", name="admin_logout")
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $session->clear();

        return $this->redirectToRoute('admin');
    }
}
