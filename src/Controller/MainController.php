<?php

namespace App\Controller;

use App\Form\CommentType;
use App\Form\LoginType;
use App\Model\ArticleManager;
use App\Model\LoginManager;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws Exception
     */
    #[Route(path: '/', name: 'main')]
    public function index(Request $request): RedirectResponse|Response
    {
        $doctrine = $this->managerRegistry;
        $session = $request->getSession();
        $user_id = $session->get('user_id');
        $user_name = $session->get('user_name');

        $articleManager = new ArticleManager();
        $articles_and_comments = $articleManager->getAllArticlesWithComments($doctrine);
        if (!empty($user_id) && !empty($user_name)){ // user is LOGGED IN

            // generate CommentType forms
            $comment_forms = [];
            foreach ($articles_and_comments['articles'] as $article) {
                $comment_form = $this->createForm(CommentType::class)->createView();
                $comment_forms[$article['id']] = $comment_form;
            }

            // handle CommentType request
            $comment_form = $this->createForm(CommentType::class);
            $comment_form->handleRequest($request);

            if ($comment_form->isSubmitted() && $comment_form->isValid()) { // handle comment form
                $form_data = $comment_form->getData();
                $saved = $articleManager->processSaveComment($doctrine, $user_id, $form_data);

                if ($saved === true) {
                    $message_type = 'success';
                    $message = 'Komentář byl úspěšně uložen';
                } else {
                    $message_type = 'error';
                    $message = $saved;
                }

                $url = $this->generateUrl('main', [
                    'message' => $message,
                    'message_type' => $message_type
                ]);

                return $this->redirect($url.'#message');
            }

            return $this->render('main/index.html.twig', [
                'articles' => $articles_and_comments['articles'],
                'comments' => $articles_and_comments['comments'],
                'comment_forms' => $comment_forms,
                'user_id' => $user_id,
                'user_name' => $user_name
            ]);

        } else { // user is NOT LOGGED in
            $login_form = $this->createForm(LoginType::class);

            $login_form->handleRequest($request);
            if ($login_form->isSubmitted() && $login_form->isValid()) { // handle LOGIN form
                $form_data = $login_form->getData();
                $name = htmlspecialchars(strip_tags($form_data->getName()));
                $pass = hash('sha512', htmlspecialchars(strip_tags($form_data->getPass())));

                $loginManager = new LoginManager();
                $login = $loginManager->processLogin($doctrine, $name, $pass, 'user');
                if ($login['logged'] === true) {
                    $session->set('user_id', $login['user_id']);
                    $session->set('user_name', $login['user_name']);

                    return $this->redirectToRoute('main');
                } elseif ($login['logged'] === false) {
                    $message_type = 'error';
                    $message = $login['message'];

                    $url = $this->generateUrl('main', [
                        'message' => $message,
                        'message_type' => $message_type
                    ]);

                    return $this->redirect($url.'#message');
                }
            }

            return $this->render('main/index.html.twig', [
                'articles' => $articles_and_comments['articles'],
                'comments' => $articles_and_comments['comments'],
                'login_form' => $login_form
            ]);

        }

    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    #[Route(path: '/logout', name: 'logout')]
    public function processLogout(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $session->clear();

        return $this->redirectToRoute('main');
    }
}
