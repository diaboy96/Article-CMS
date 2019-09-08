<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Login;
use App\Form\CommentType;
use App\Form\LoginType;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function index(Request $request)
    {
        $doctrine = $this->getDoctrine();
        $session = new Session();

        $login_form = $this->createForm(LoginType::class);

        $login_form->handleRequest($request);
        if ($login_form->isSubmitted() && $login_form->isValid()) {
            $form_data = $login_form->getData();
            $name = htmlspecialchars(strip_tags($form_data->getName()));
            $pass = hash('sha512', htmlspecialchars(strip_tags($form_data->getPass())));

            $login = $this->processLogin($doctrine, $name, $pass);
            if ($login['logged'] === true) {
                $session->set('user_id', $login['user_id']);
                $session->set('user_name', $login['user_name']);
                $message_type = 'success';
                $message = 'Přihlášení proběhlo úspěšně';
            } elseif ($login['logged'] === false) {
                $message_type = 'error';
                $message = $login['message'];
            }


            $url = $this->generateUrl('main', [
                'message' => $message,
                'message_type' => $message_type
            ]);

            return $this->redirect($url.'#message');
        }

        $user_id = $session->get('user_id');
        $user_name = $session->get('user_name');
        $articles_and_comments = $this->getAllArticlesWithComments($doctrine);
        if (isset($user_id) && !empty($user_id) && isset($user_name) && !empty($user_name)){ // user is LOGGED IN

            // generate CommentType forms
            $comment_forms = [];
            foreach ($articles_and_comments['articles'] as $article) {
                $comment_form = $this->createForm(CommentType::class)->createView();
                $comment_forms[$article['id']] = $comment_form;
            }

            // handle CommentType request
            $comment_form = $this->createForm(CommentType::class);
            $comment_form->handleRequest($request);

            if ($comment_form->isSubmitted() && $comment_form->isValid()) {
                $form_data = $comment_form->getData();
                $saved = $this->processSaveComment($doctrine, $user_id, $form_data);

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

            return $this->render('main/index.html.twig', [
                'articles' => $articles_and_comments['articles'],
                'comments' => $articles_and_comments['comments'],
                'login_form' => $login_form->createView()
            ]);

        }

    }

    /**
     * @param ManagerRegistry $doctrine
     * @param $name
     * @param $pass
     * @return array
     */
    private function processLogin(ManagerRegistry $doctrine, $name, $pass)
    {
        $user = $doctrine->getRepository(Login::class)->findOneBy(['name' => $name, 'pass' => $pass]);
        $message = '';

        if ($user) {
            $user_active = $user->getActive();

            if ($user_active == 1) {
                return ['logged' => true, 'user_id' => $user->getId(), 'user_name' => $user->getName()];
            } elseif ($user_active == 'pending') {
                $message = 'Váš účet není aktivní. Aktivujte ho pomocí odkazu v emailu.';
            } elseif ($user_active == 0) {
                $message = 'Váš účet je zablokován. Kontaktujte prosím správce webu.';
            }

        } else {
            $message = 'Jméno nebo heslo není správné';
        }

        return ['logged' => false, 'message' => $message];
    }

    /**
     * @Route("/logout", name="logout")
     * @return RedirectResponse
     */
    public function processLogout()
    {
        $session = new Session();
        $session->clear();

        $url = $this->generateUrl('main', [
            'message' => 'Odhlášení proběhlo úspěšně',
            'message_type' => 'success'
        ]);

        return $this->redirect($url.'#message');
    }

    /**
     * @param ManagerRegistry $doctrine
     * @return array
     */
    private function getAllArticlesWithComments(ManagerRegistry $doctrine)
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
            $message = "Zadejte hodnotu komentare";

            return $message;
        }
        return true;
    }
}
