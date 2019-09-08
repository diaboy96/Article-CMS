<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index()
    {
        // todo: vytvorit univerzalni metodu (metody) v MainController pro vypsani clanku, vytvoreni formu na commenty, prihlaseni, ...
        // todo: zavolat si tyto metody odsud a predat jim parametry

        return $this->render('admin/index.html.twig', [
            'admin_id' => 0
        ]);
    }

    /**
     * @Route("/admin/logout", name="admin_logout")
     * @return RedirectResponse
     */
    public function logout()
    {
        $session = new Session();
        $session->clear();

        return $this->redirectToRoute('admin');
    }
}
