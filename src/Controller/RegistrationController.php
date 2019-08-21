<?php

namespace App\Controller;

use App\Entity\Login;
use App\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/registration", name="registration")
     */
    public function index(Request $request)
    {
        $registration_form = $this->createForm(RegisterType::class);
        $registration_form->handleRequest($request);
        if ($registration_form->isSubmitted() && $registration_form->isValid()) {
            $data = $registration_form->getData();
            $name = htmlspecialchars(strip_tags($data['name']));
            $pass = htmlspecialchars(strip_tags(hash('sha512', $data['pass'])));
            $pass_again = htmlspecialchars(strip_tags(hash('sha512', $data['pass_again'])));
            $email = htmlspecialchars(strip_tags($data['email']));
            $hash = hash('sha512', rand(0,5000));

            if ($pass == $pass_again) {
                // send verification email with hash
                $to      = $email;
                $subject = 'Ověření registrace';
                $message = '
 
Děkujeme Vám za registraci v Article CMS!
Váš účet byl úspěšně vytvořen, poté co provedete aktivaci Vašeho účtu (kliknutím na odkaz dole) se můžete přihlásit. 
 
Pro aktivaci účtu prosím klikněte na následující link:

'.$request->server->get('HTTP_HOST').'/registration/activation/'.$email.'/'.$hash;

                $headers = 'From:noreply@'.$request->server->get('HTTP_HOST'). "\r\n"; // Set from headers
                mail($to, $subject, $message, $headers);
                // save data to db
                /*
                $entityManager = $this->getDoctrine()->getManager();
                $login = new Login();
                $login->setName($name);
                $login->setPass($pass);
                $login->setEmail($email);
                $login->setHash($hash);
                $login->setActive('pending');
                $entityManager->persist($login);
                $entityManager->flush();
                */
            } else {
                // todo Error hesla se neshodji
            }
        }

        return $this->render('registration/index.html.twig', [
            'registration_form' => $registration_form->createView()
        ]);
    }
}
