<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Login;
use App\Form\LoginType;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function index(Request $request)
    {
        $session = new Session();

        $login_form = $this->createForm(LoginType::class);

        $login_form->handleRequest($request);
        if ($login_form->isSubmitted() && $login_form->isValid()) {
            $form_data = $login_form->getData();
            $name = htmlspecialchars(strip_tags($form_data->getName()));
            $pass = hash('sha512', htmlspecialchars(strip_tags($form_data->getPass())));

            $login = $this->login($name, $pass);
            if ($login['logged'] === true) {
                $session->set('user_id', $login['user_id']);
                $session->set('user_name', $login['user_name']);
            } elseif ($login['logged'] === false) {
                dump($login['message']); // todo message to frontend - Login False
            }

        }

        $user_id = $session->get('user_id');
        $user_name = $session->get('user_name');
        if (isset($user_id) && !empty($user_id) && isset($user_name) && !empty($user_name)){

            return $this->logged_in($user_id, $user_name); // display view if user is logged

        } else {

            return $this->logged_out($login_form); // display view if user is not logged

        }

    }

    private function login($name, $pass)
    {
        $user = $this->getDoctrine()->getRepository(Login::class)->findOneBy(['name' => $name, 'pass' => $pass]);
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
     */
    public function logout()
    {
        $session = new Session();
        $session->clear();

        return $this->redirectToRoute('main', ['message' => 'Odhlášení proběhlo úspěšně']);
    }

    private function logged_in($user_id, $user_name)
    {
        return $this->showAllArticles();
/* todo uncomment
        return $this->render('main/logged_in.html.twig', [
            'user_name' => $user_name
        ]);*/
    }

    private function logged_out($login_form)
    {
        return $this->render('main/index.html.twig', [ //todo add articles to this template (make new base template and extend it)
            'login_form' => $login_form->createView()
        ]);
    }

    private function showAllArticles()
    {
        $doctrine = $this->getDoctrine();

        $articles = $doctrine
            ->getRepository(Article::class)
            ->createQueryBuilder('a')
            ->addOrderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $comments = $doctrine
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->join(Login::class, 'l', 'WITH', 'c.user_id = l.id')
            ->addOrderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        dump($articles, $comments);

        return $this->render('article/article.html.twig', ['articles' => $articles]);
    }
}
