<?php

namespace App\Controller;

use App\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function index()
    {
        $login_form = $this->createForm(LoginType::class);

        return $this->render('main/index.html.twig', [
            'login_form' => $login_form->createView()
        ]);
    }
}
