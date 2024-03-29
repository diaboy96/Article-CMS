<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Login;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

class AccountController extends AbstractController
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    #[Route(path: '/admin/accountOverview', name: 'account_overview')]
    public function index(Request $request): RedirectResponse|Response
    {
        $admin_is_logged_in = AdminController::checkIfAdminIsLoggedIn($request);
        if ($admin_is_logged_in) {

            $users = $this->managerRegistry
                ->getRepository(Login::class)
                ->fetchAllDataExceptPasswords();

            $session = $request->getSession();
            $admin_id = intval($session->get('admin_id'));

            return $this->render('account/index.html.twig', [
                'header' => 'Registrovaní uživatelé',
                'admin_id' => $admin_id,
                'back_to_home_icon' => true,
                'table' => 'registered_users',
                'db' => $users
            ]);

        } else {
            return $this->redirectToRoute('admin');
        }
    }

    /**
     * @param Request $request
     * @param $user_id
     * @return RedirectResponse
     */
    #[Route(path: 'admin/activateUser/{user_id}', name: 'activate_user', defaults: ['user_id' => 'not_set'])]
    public function activateUser(Request $request, $user_id): RedirectResponse
    {
        $admin_is_logged_in = AdminController::checkIfAdminIsLoggedIn($request);
        $user_id = intval($user_id);
        $doctrine = $this->managerRegistry;

        if ($admin_is_logged_in) {
            $user = $doctrine
                ->getRepository(Login::class)
                ->findOneBy([
                    'id' => $user_id
                ]);

            if ($user) {
                $active_state = $user->getActive();
                if ($active_state == 1) {
                    // deactivate account
                    $user->setActive(0);
                } elseif ($active_state == 0) {
                    // activate account
                    $user->setActive(1);
                }
                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('account_overview');
            } else {
                $url = $this->generateUrl('account_overview', [
                    'message' => 'Nelze provést aktivaci / deaktivaci. Uživatel nebyl nalezen v databázi.',
                    'message_type' => 'error'
                ]);
                return $this->redirect($url."#message");
            }
        } else {
            return $this->redirectToRoute('admin');
        }
    }

    /**
     * @param Request $request
     * @param $user_id
     * @return RedirectResponse|Response
     */
    #[Route(path: 'admin/userComments/{user_id}', name: 'user_comments', defaults: ['user_id' => 'not_set'])]
    public function userComments(Request $request, $user_id): RedirectResponse|Response
    {
        $admin_is_logged_in = AdminController::checkIfAdminIsLoggedIn($request);
        $user_id = intval($user_id);
        $doctrine = $this->managerRegistry;

        if ($admin_is_logged_in) {
            $comments = $doctrine
                ->getRepository(Comment::class)
                ->fetchCommentsByUserId($user_id);

            $user = $doctrine
                ->getRepository(Login::class)
                ->findOneBy([
                    'id' => $user_id
                ]);

            if ($comments) {
                $user_name = $user->getName();
                $session = $request->getSession();
                $admin_id = intval($session->get('admin_id'));

                return $this->render('account/index.html.twig', [
                    'header' => 'Komentáře uživatele ' . $user_name,
                    'admin_id' => $admin_id,
                    'back_to_home_icon' => true,
                    'table' => 'user_comments',
                    'db' => $comments
                ]);
            } else {
                $url = $this->generateUrl('account_overview', [
                    'message' => 'Tento uživatel nemá žádné komentáře',
                    'message_type' => 'info'
                ]);
                return $this->redirect($url.'#message');
            }
        } else {
            return $this->redirectToRoute('admin');
        }
    }

    /**
     * @param Request $request
     * @param $user_id
     * @return RedirectResponse
     */
    #[Route(path: '/admin/removeUserAccount/{user_id}', name: 'user_account_remove', defaults: ['user_id' => 'not_set'])]
    public function removeUserAccount(Request $request, $user_id): RedirectResponse
    {
        $admin_is_logged_in = AdminController::checkIfAdminIsLoggedIn($request);
        if ($admin_is_logged_in) {

            $doctrine = $this->managerRegistry;
            $entityManager = $doctrine->getManager();
            $user_id = intval($user_id);
            $user = $doctrine
                ->getRepository(Login::class)
                ->findOneBy([
                    'id' => $user_id
                ]);

            if ($user) {
                $entityManager->remove($user);
                $entityManager->flush();

                $user_owned_comments_were_removed = $this->removeUserComments($doctrine, $user_id);

                if ($user_owned_comments_were_removed) {
                    $url = $this->generateUrl('account_overview', [
                        'message' => 'Uživatelský účet byl úspěšně vymazán včetně všech vlastněných komentářů',
                        'message_type' => 'success'
                    ]);
                } else {
                    $url = $this->generateUrl('account_overview', [
                        'message' => 'Uživatelský účet byl úspěšně vymazán, ale jím vlastněné komentáře se vymazat nepodařilo',
                        'message_type' => 'error'
                    ]);
                }

                return $this->redirect($url."#message");
            } else {
                $url = $this->generateUrl('account_overview', ['message' => 'Uživatelský účet nebyl nalezen v databázi', 'message_type' => 'error']);
                return $this->redirect($url.'#message');
            }
        } else {
            return $this->redirectToRoute('admin');
        }
    }

    /**
     * @param ManagerRegistry $doctrine
     * @param int $user_id
     * @return bool
     */
    private function removeUserComments(ManagerRegistry $doctrine, int $user_id): bool
    {
        $comments = $doctrine
            ->getRepository(Comment::class)
            ->findBy([
                'user_id' => $user_id
            ]);

        $entityManager = $doctrine->getManager();

        foreach ($comments as $comment) {
            $entityManager->remove($comment);
        }

        $entityManager->flush();

        return true;
    }
}
