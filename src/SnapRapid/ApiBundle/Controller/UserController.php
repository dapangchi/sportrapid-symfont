<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Hateoas\Configuration\Route;
use Hateoas\Representation\Factory\PagerfantaFactory;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SnapRapid\ApiBundle\Form\ActivateAccountType;
use SnapRapid\ApiBundle\Security\User\SecurityUser;
use SnapRapid\Core\Exception\CoreException;
use SnapRapid\Core\Model\Notification;
use SnapRapid\Core\Model\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserController
 *
 * @Rest\Route("/user")
 */
class UserController extends BaseController
{
    /**
     * User registration
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Register",
     *   resource = true,
     *   input = { "class" = "user_form", "name" = "" },
     *   output = "SnapRapid\Core\Model\User",
     *   statusCodes = {
     *     201 = "User registered successfully",
     *     400 = "Errors in the submitted form",
     *     409 = "There is a conflict with an existing User"
     *   }
     * )
     *
     * @Rest\Post("", name="create_user")
     * @Rest\RequestParam(
     *   name = "user",
     *   description = "User form",
     *   array = true,
     *   strict = true
     * )
     * @Rest\View(serializerGroups={"Default", "UserSelf"})
     *
     * @param Request $request
     *
     * @return View
     */
    public function registerAction(Request $request)
    {
        $user = $this->get('user_manager')->createNewUser();
        $view = $this->processUserForm($user, $request, $this->getUser());

        // set the serializer groups
        $serializerGroups = $this->getSerializerGroupsFromAnnotations();
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $serializerGroups[] = 'UserEdit';
        } else {
            $serializerGroups[] = 'UserSelf';
        }
        $this->setSerializerGroups($view, $serializerGroups);

        return $view;
    }

    /**
     * Activate a user's account
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Activate Account",
     *   statusCodes = {
     *     200 = "Account activated successfully and user is logged in",
     *     400 = "Token not valid",
     *     401 = "Authentication required"
     *   }
     * )
     *
     * @Rest\Post("/activate", name="api_activate_account")
     * @Rest\RequestParam(
     *   name = "token",
     *   description = "Account activation token",
     *   requirements = ".+",
     *   strict = true
     * )
     *
     * @Rest\View(serializerGroups={"Default", "UserSelf"})
     *
     * @param ParamFetcherInterface $paramFetcher
     * @param Request               $request
     *
     * @return View
     */
    public function activateAccountAction(ParamFetcherInterface $paramFetcher, Request $request)
    {
        $userManager = $this->get('user_manager');
        $user        = $userManager->findUserByAccountActivationToken($paramFetcher->get('token'));

        // validate using forms - note form updates user object with incoming password and token
        $form = $this->createForm(
            new ActivateAccountType(),
            $user
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $userManager->activateAccount($user, $newPassword);

            // decorate the user
            $userManager = $this->get('user_manager');
            $userManager->decorateUser($user, true);

            // create a new auth token for the user and return it so they can be logged in automatically
            $securityUser = new SecurityUser($user);
            $jwtManager   = $this->get('lexik_jwt_authentication.jwt_manager');
            $token        = $jwtManager->create($securityUser);

            return View::create(
                [
                    'token' => $token,
                    'user'  => $user,
                ]
            );
        }

        return View::create($form, 400);
    }

    /**
     * Resend account activation email
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Resend activation email",
     *   statusCodes = {
     *     204 = "Account activation email resent",
     *     400 = "User does not require activation",
     *     401 = "Authentication required"
     *   }
     * )
     *
     * @Rest\Post("/activate/resend", name="api_activation_resend")
     * @Rest\RequestParam(
     *   name = "email",
     *   description = "Email",
     *   requirements = ".+",
     *   strict = true
     * )
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return View
     */
    public function resendAccountActivationAction(ParamFetcherInterface $paramFetcher)
    {
        $email       = $paramFetcher->get('email');
        $userManager = $this->get('user_manager');

        // get user
        $user = $userManager->findUserByEmail($email);
        if (!$user) {
            return View::create(
                [
                    'error' => 'This email address is not registered in the system.',
                ],
                400
            );
        }

        // if user is not activated or enabled then don't give them a reset token
        if (!$user->getAccountActivationToken()) {
            return View::create(
                [
                    'error' => 'This account has already been activated.',
                ],
                403
            );
        }

        $userManager->resendAccountActivation($user);

        return View::create(null, 204);
    }

    /**
     * Update a User's settings
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Update a User's settings",
     *   resource = true,
     *   input = { "class" = "user_form", "name" = "" },
     *   output = "SnapRapid\Core\Model\User",
     *   statusCodes = {
     *     204 = "User updated",
     *     400 = "Errors in the submitted form",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to update this User",
     *     404 = "User not found"
     *   }
     * )
     *
     * @Rest\Patch("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="update_user")
     * @Rest\RequestParam(
     *   name = "user",
     *   description = "User form",
     *   array = true,
     *   strict = false
     * )
     * @Rest\View(serializerGroups={"Default"})
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && userToUpdate.isSameUserAs(user)")
     *
     * @param User    $userToUpdate
     * @param Request $request
     *
     * @return View
     */
    public function updateUserAction(User $userToUpdate, Request $request)
    {
        $view = $this->processUserForm($userToUpdate, $request, $this->getUser());

        // set the serializer groups
        $serializerGroups = $this->getSerializerGroupsFromAnnotations();
        if ($userToUpdate->isSameUserAs($this->getSecurityUser())) {
            $serializerGroups[] = 'UserSelf';
        }
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $serializerGroups[] = 'UserEdit';
        }
        $this->setSerializerGroups($view, $serializerGroups);

        return $view;
    }

    /**
     * Get a User
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Get a User",
     *   resource = true,
     *   output = "SnapRapid\Core\Model\User",
     *   statusCodes = {
     *     200 = "User found and returned",
     *     404 = "User not found"
     *   }
     * )
     *
     * @Rest\Get("/{id}", requirements={"id": ".*"}, name="get_user")
     * @Rest\View(serializerGroups={"Default"})
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && userToFetch.isSameUserAs(user)")
     *
     * @param User $userToFetch
     *
     * @return View
     */
    public function getUserAction(User $userToFetch)
    {
        $view = View::create();
        $view->setData($userToFetch);

        // set the serializer groups
        $serializerGroups = $this->getSerializerGroupsFromAnnotations();
        if ($userToFetch->isSameUserAs($this->getSecurityUser())) {
            $serializerGroups[] = 'UserSelf';
            $this->get('user_manager')->decorateUser($userToFetch, true);
        }
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $serializerGroups[] = 'UserEdit';
        }
        $this->setSerializerGroups($view, $serializerGroups);

        return $view;
    }

    /**
     * Remove a User
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Remove a User",
     *   statusCodes = {
     *     204 = "User removed",
     *     401 = "Not authenticated",
     *     403 = "User is not authorised to remove this User",
     *     404 = "User not found"
     *   }
     * )
     *
     * @Rest\Delete("/{id}", requirements={"id": "[a-zA-Z0-9]+"}, name="remove_user")
     * @Rest\View
     *
     * @Security("has_role('ROLE_ADMIN') || has_role('ROLE_USER') && userToRemove.isSameUserAs(user)")
     *
     * @param User $userToRemove
     */
    public function removeUserAction(User $userToRemove)
    {
        $this->get('user_manager')->removeUser($userToRemove);
    }

    /**
     * Respond to a notification
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Respond to a notification",
     *   statusCodes = {
     *     200 = "Response noted and actioned",
     *     400 = "Something went wrong - see message",
     *     401 = "Not authenticated",
     *     403 = "User is not the user the notification was intended for",
     *     404 = {
     *       "User not found",
     *       "Notification not found"
     *     }
     *   }
     * )
     *
     * @Rest\Patch(
     *   "/{id}/notification/{notificationId}",
     *   requirements={"id": "[a-zA-Z0-9]+", "notificationId": "[a-zA-Z0-9]+"},
     *   name="notification_response"
     * )
     * @ParamConverter("notification", class="SnapRapidApiBundle:Notification", options={"id" = "notificationId"})
     * @Rest\View
     *
     * @Security("respondingUser.isSameUserAs(user) && notification.isIntendedFor(user)")
     *
     * @param User         $respondingUser
     * @param Notification $notification
     * @param Request      $request
     *
     * @return View
     */
    public function notificationResponseAction(User $respondingUser, Notification $notification, Request $request)
    {
        try {
            $successMessage = $this->get('notification_manager')->handleNotificationResponse(
                $notification,
                $request->request->get('response')
            );

            return View::create(['success' => $successMessage], 200);
        } catch (CoreException $e) {
            return View::create(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get a collection of users.
     *
     * @ApiDoc(
     *   section = "User",
     *   description = "Get a collection of Users",
     *   statusCodes = {
     *     200 = "User collection found and returned",
     *     403 = "Not authorised",
     *     401 = "Not authenticated"
     *   }
     * )
     *
     * @Rest\Get("", name="get_user_collection")
     *
     * @Rest\QueryParam(
     *   name = "filter",
     *   array = true,
     *   description = "Filters to apply"
     * )
     * @Rest\QueryParam(
     *   name = "sorting",
     *   array = true,
     *   description = "Order in which to return results"
     * )
     * @Rest\QueryParam(
     *   name = "count",
     *   requirements = "\d+",
     *   default = 25,
     *   description = "Number of Users to return"
     * )
     * @Rest\QueryParam(
     *   name = "page",
     *   requirements = "\d+",
     *   default = 1,
     *   description = "Page number"
     * )
     * @Rest\View(serializerGroups={"Default", "UserList"})
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function getUserCollectionAction(ParamFetcherInterface $paramFetcher)
    {
        $pager = $this->get('user_repository')->getResultsPager(
            $paramFetcher->get('filter'),
            $paramFetcher->get('sorting'),
            $paramFetcher->get('count'),
            $paramFetcher->get('page')
        );

        $pagerFactory = new PagerfantaFactory();

        return $pagerFactory->createRepresentation(
            $pager,
            new Route('get_user_collection', $paramFetcher->all())
        );
    }

    /**
     * Process an User form (create or update)
     *
     * @param User    $user
     * @param Request $request
     * @param User    $activeUser
     *
     * @return View
     */
    private function processUserForm(User $user, Request $request, User $activeUser = null)
    {
        $isNew = $user->isNew();
        if ($isNew) {
            $validationGroups = ['Default'];
            if (!$activeUser) {
                $validationGroups[] = 'Invitation';
            }
        } else {
            $oldUser = clone $user;
            if ($request->request->has('currentPassword')) {
                $validationGroups = ['ChangePassword'];
            } else {
                $validationGroups = ['Default', 'Register'];
            }
        }
        $form = $this->createApiForm(
            'user_form',
            $user,
            [
                'method'            => $isNew ? 'POST' : 'PATCH',
                'validation_groups' => $validationGroups,
                'roles'             => $activeUser ? $activeUser->getRoles() : [],
            ]
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            // set admin changes - double check only admin users allowed to do this
            if ($form->has('role') && $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
                $user->setRole($form->get('role')->getData());
            }

            $userManager = $this->get('user_manager');
            if ($isNew) {
                $userManager->saveNewUser($user, $activeUser);

                // create a new auth token for the user and return it so they can be logged in automatically
                $securityUser = new SecurityUser($user);
                $jwtManager   = $this->get('lexik_jwt_authentication.jwt_manager');
                $token        = $jwtManager->create($securityUser);

                return View::create(
                    [
                        'token' => $token,
                        'user'  => $user,
                    ],
                    201,
                    [
                        'Location' => $this->generateUrl(
                            'get_user',
                            ['id' => $user->getId()],
                            true
                        ),
                    ]
                );
            } else {
                $userManager->updateUser($user, $oldUser);
                $userManager->decorateUser($user, true);

                return View::create($user, 200);
            }
        }

        // form was not valid
        return View::create($form, 400);
    }
}
