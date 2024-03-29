<?php

namespace App\Model;
use App\Entity\Login;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

class LoginManager
{
    /**
     * @param ManagerRegistry $doctrine
     * @param $name
     * @param $pass
     * @param $section
     * @return array
     * @throws Exception
     */
    public function processLogin(ManagerRegistry $doctrine, $name, $pass, $section)
    {
        if ($section == 'user' || $section == 'admin') {

            $login_repository = $doctrine->getRepository(Login::class);
            $message = '';
            if ($section == 'user') {
                $login = $login_repository
                    ->findOneBy([
                        'name' => $name,
                        'pass' => $pass
                    ]);

            } elseif ($section == 'admin') {
                $login = $login_repository
                    ->findOneBy([
                        'name' => $name,
                        'pass' => $pass,
                        'type' => 'admin'
                    ]);
            }

            if ($login) {
                $login_active = $login->getActive();

                if ($login_active == 1) { // logged in
                    return ['logged' => true, $section . '_id' => $login->getId(), $section . '_name' => $login->getName()];
                } elseif ($login_active == 'pending') {
                    $message = 'Váš účet není aktivní. Aktivujte ho pomocí odkazu v emailu.';
                } elseif ($login_active == 0) {
                    $message = 'Váš účet je zablokován. Kontaktujte prosím správce webu.';
                }

            } elseif ($section == 'admin') {
                $login = $login_repository
                    ->findOneBy([
                        'name' => $name,
                        'pass' => $pass
                    ]);

                if ($login) {
                    $message = "Pro přístup do sekce pro administrátory nemáte patřičná oprávnění.";
                } else {
                    $message = 'Jméno nebo heslo není správné';
                }

            } else {
                $message = 'Jméno nebo heslo není správné';
            }

            return ['logged' => false, 'message' => $message];
        } else {
            throw new Exception('variable $section must be "user" or "admin"');
        }
    }
}
