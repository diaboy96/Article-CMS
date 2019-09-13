<?php


namespace App\Model;


use App\Entity\Login;
use Doctrine\Common\Persistence\ManagerRegistry;

class LoginManager
{
    /**
     * @param ManagerRegistry $doctrine
     * @param $name
     * @param $pass
     * @param $section
     * @return array
     */
    public function processLogin(ManagerRegistry $doctrine, $name, $pass, $section)
    {
        $login_repository = $doctrine->getRepository(Login::class);
        if ($section == 'user') {
            $login = $login_repository
                ->findOneBy([
                    'name' => $name,
                    'pass' => $pass
                ]);
            $message = '';
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
                return ['logged' => true, $section.'_id' => $login->getId(), $section.'_name' => $login->getName()];
            } elseif ($login_active == 'pending') {
                $message = 'Váš účet není aktivní. Aktivujte ho pomocí odkazu v emailu.';
            } elseif ($login_active == 0) {
                $message = 'Váš účet je zablokován. Kontaktujte prosím správce webu.';
            }

        } elseif($section == 'admin') {

            $login = $login_repository
                ->findOneBy([
                    'name' => $name,
                    'pass' => $pass
                ]);

            if ($login) {
                $message = "Pro přístup do sekce pro administrátory nemáte patřičná oprávnění.";
            }

        } else {
            $message = 'Jméno nebo heslo není správné';
        }

        return ['logged' => false, 'message' => $message];

    }
}