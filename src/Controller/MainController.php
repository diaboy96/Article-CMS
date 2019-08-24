<?php

namespace App\Controller;

use App\Entity\Login;
use App\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController //todo check all code
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
            $pass = htmlspecialchars(strip_tags(hash('sha512', $form_data->getPass())));

            $login = $this->login($name, $pass);
            if ($login['logged'] === true) {
                $session->set('user_id', $login['user_id']);
                $session->set('user_name', $login['user_name']);
            } elseif ($login['logged'] === false) {
                dump($login['message']); // todo message to frontend
            }

        }

        $user_id = $session->get('user_id');

        if (isset($user_id) && !empty($user_id)){

            return $this->logged_in();

        } else {

            return $this->render('main/index.html.twig', [
                'login_form' => $login_form->createView()
            ]);
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
                $message = 'Váš účet je zablokován.';
            }

        } else {
            $message = 'Jméno nebo heslo není správné';
        }

        return ['logged' => false, 'message' => $message];
    }

    private function logged_in()
    {
      //todo add logout  $session->clear();
        //todo add freontend render + create new template
    }
}
