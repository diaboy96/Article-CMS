<?php

namespace App\Controller;

use App\Entity\Login;
use App\Form\RegisterType;
use App\Repository\LoginRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/registration", name="registration")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function index(Request $request)
    {
        $registration_form = $this->createForm(RegisterType::class);
        $registration_form->handleRequest($request);

        if ($registration_form->isSubmitted() && $registration_form->isValid()) {
            $form_data = $registration_form->getData();

            return $this->register($request, $form_data);
        }

        return $this->render('registration/index.html.twig', [
            'registration_form' => $registration_form->createView()
        ]);
    }

    /**
     * @Route("/registration/activation/{email}/{hash}", name="registration_activation", defaults={"email" = "not_set", "hash" = "not_set"})
     * @param $email
     * @param $hash
     * @return RedirectResponse
     */
    public function activation($email, $hash)
    {
        $error_code = '';
        $message = '';

        if ($email !== 'not_set' && $hash !== 'not_set') {
            $email = htmlspecialchars(strip_tags($email));
            $hash = htmlspecialchars(strip_tags($hash));
            $login_repository = $this->getDoctrine()->getRepository(Login::class);

            $user = $login_repository->findOneBy(['email' => $email,
                        'hash' => $hash]
            );

            if ($user) { // uzivatel s emailem a hashem existuje (hash souhlasi) a vse je ok
                $entityManager = $this->getDoctrine()->getManager();
                $user->setHash('');
                $user->setActive(1);
                $entityManager->persist($user);
                $entityManager->flush();

                $message = "Aktivce uživatelského účtu proběhla úspěšně";
            } else {
                $get_email = $login_repository->findOneBy(['email' => $email]);
                if (!$get_email) {
                    $error_code = "A1";
                } elseif ($get_email) {
                    $get_hash = $get_email->getHash();
                    $get_active = $get_email->getActive();

                    if ($get_active == '1') {
                        $message = "Uživatelský účet je již aktivní.";
                    } elseif ($get_active == '0') {
                        $message = "Nelze aktivovat. Uživatelský účet je zablokovaný.";
                    } elseif ($hash !== $get_hash) {
                        $error_code = "A4";
                    }
                }
            }

        } else {
            if ($email == 'not_set') {
                $error_code = "A2";
            } elseif ($hash == 'not_set') {
                $error_code = "A3";
            }
        }

        if (empty($message)) {
            $message = "Uživatelský účet se nepodařilo aktivovat (Error_code:".$error_code.")";
            $message_type = "error";
        } else {
            $message_type = "success";
        }

        $url = $this->generateUrl('main', [
            'message' => $message,
            'message_type' => $message_type
        ]);

        return $this->redirect($url.'#message');
        /*
         * Error_code = A1 ---> Route="registration_activation" - Zadaný email nebyl nalezen v databázi
         * Error_code = A2 ---> Route="registration_activation" - Email nebyl zadán v URL adrese
         * Error_code = A3 ---> Route="registration_activation" - Hash nebyl zadán v URL adrese
         * Error_code = A4 ---> Route="registration_activation" - Zadaný hash v URL se neshoduje s hashem v databázi
         * */
    }

    /**
     * @param Request $request
     * @param $form_data
     * @return RedirectResponse
     */
    private function register(Request $request,$form_data)
    {
        $name = htmlspecialchars(strip_tags($form_data['name']));
        $pass = htmlspecialchars(strip_tags(hash('sha512', $form_data['pass'])));
        $pass_again = htmlspecialchars(strip_tags(hash('sha512', $form_data['pass_again'])));
        $email = htmlspecialchars(strip_tags($form_data['email']));
        $hash = hash('sha512', rand(0,5000));
        $message = '';
        $message_type = '';
        $route = '';

        if ($pass == $pass_again) {

            if (strlen($form_data['pass']) > 7 && preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $form_data['pass'])) {
                $login_repository = $this->getDoctrine()->getRepository(Login::class);
                $name_check = $this->checkIfIsInDb($login_repository, 'name', $name);
                $email_check = $this->checkIfIsInDb($login_repository, 'email', $email);

                if (!$name_check && !$email_check) { // entered name and email are not in database yet
                    $sent = $this->sendVerificationMail($request, $email, $hash); // send verification email with hash

                    if ($sent) {
                        $saved = $this->saveToDb($name, $pass, $email, $hash); // save data to db
                        if ($saved) {
                            $message = 'Registrace proběhla úspěšně, prosím proveďte aktivaci v emailu';
                            $message_type = 'success';
                            $route = 'main';
                        } else {
                            $message = 'Nelze vytvořit uživatelský účet, kontaktujte prosím správce webu. (Error_code=R1)';
                        }
                    } else {
                        $message = 'Nelze vytvořit uživatelský účet, kontaktujte prosím správce webu. (Error_code=R2)';
                    }
                } elseif ($name_check) {
                    $message = 'Toto jméno je již obsazené';
                } elseif ($email_check) {
                    $message = 'Zadaný email byl již zaregistrován';
                }
            } else {
                $message = 'Heslo musí mít minimálně 8 znaků a obsahovat alespoň jednu číslici';
            }

        } else {
            $message = 'Zadaná hesla se neshodují';
        }

        if (empty($message_type)) {
            $message_type = 'error';
            $route = 'registration';
        }


        $url = $this->generateUrl($route, [
            'message' => $message,
            'message_type' => $message_type
        ]);

        return $this->redirect($url.'#message');
        /*
         * Error_code = R1 ---> Route="registration", Method="saveToDb" - Nepodařilo se uložit data do databáze
         * Error_code = R2 ---> Route="registration", Method="sendVerificationEmail" - Nepodařilo se odeslat ověřovací email
         */
    }

    /**
     * @param LoginRepository $login_repository
     * @param $column
     * @param $value
     * @return bool
     */
    private function checkIfIsInDb(LoginRepository $login_repository, $column, $value)
    {
        $response = $login_repository->findBy([
            $column => $value
        ]);

        if ($response) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Request $request
     * @param $email
     * @param $hash
     * @return bool
     */
    private function sendVerificationMail(Request $request, $email, $hash)
    {
        $to = $email;
        $subject = 'Ověření registrace';
        $email_message = '
 
Děkujeme Vám za registraci v Article CMS!
Váš účet byl úspěšně vytvořen, poté co provedete aktivaci Vašeho účtu (kliknutím na odkaz dole) se můžete přihlásit. 
 
Pro aktivaci účtu prosím klikněte na následující link:

' . $request->server->get('HTTP_HOST') . '/registration/activation/' . $email . '/' . $hash;

        $headers = 'From:noreply@' . $request->server->get('HTTP_HOST') . "\r\n";
        mail($to, $subject, $email_message, $headers);

        return true;
    }

    /**
     * @param string $name
     * @param string $pass
     * @param string $email
     * @param string $hash
     * @return boolean
     */
    private function saveToDb(string $name, string $pass, string $email, string $hash)
    {
        $entityManager = $this->getDoctrine()->getManager();
        $login = new Login();
        $login->setName($name);
        $login->setPass($pass);
        $login->setEmail($email);
        $login->setHash($hash);
        $login->setActive('pending');
        $login->setType('user');
        $entityManager->persist($login);
        $entityManager->flush();

        return true;
    }
}