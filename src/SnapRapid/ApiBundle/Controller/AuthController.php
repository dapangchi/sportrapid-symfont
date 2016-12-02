<?php

namespace SnapRapid\ApiBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use SnapRapid\ApiBundle\Form\ResetPasswordType;
use SnapRapid\ApiBundle\Security\User\SecurityUser;
use SnapRapid\Core\Model\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthController
 *
 * @Rest\Route("/auth")
 */
class AuthController extends BaseController
{
    /**
     * Get an API JWT token for authentication.
     * It will attempt to return an authenticated user token for given credentials.
     *
     * @ApiDoc(
     *   section = "Authentication",
     *   description = "Log in / Get API token",
     *   statusCodes = {
     *     200 = "Authentication successful",
     *     401 = "Authentication information was incorrect",
     *     404 = "User not found in the system"
     *   }
     * )
     *
     * @Rest\Post("/login", name="get_token")
     *
     * @Rest\RequestParam(
     *   name = "username",
     *   description = "Username",
     *   strict = false
     * )
     * @Rest\RequestParam(
     *   name = "password",
     *   description = "Password",
     *   strict = false
     * )
     */
    public function loginAction()
    {
        // handled by the firewall
    }

    /**
     * Refresh an API JWT token for authentication.
     * The existing (valid) token should be passed for authentication.
     * An updated token will be returned with an extended ttl
     *
     * @ApiDoc(
     *   section = "Authentication",
     *   description = "Refresh API token",
     *   statusCodes = {
     *     200 = "Token refresh successful",
     *     401 = "Passed token was invalid/expired",
     *     403 = "User is no longer allowed access"
     *   }
     * )
     *
     * @Rest\Get("/refresh", name="refresh_token")
     */
    public function refreshAction()
    {
        $securityUser = $this->getSecurityUser();

        // check if the user is still enabled
        if (!$securityUser->getUser()->isEnabled()) {
            return View::create(null, 403);
        }

        // create a new token for the user
        $jwtManager = $this->get('lexik_jwt_authentication.jwt_manager');
        $token      = $jwtManager->create($securityUser);

        return View::create(
            [
                'token' => $token,
            ]
        );
    }

    /**
     * Request a password reset email to be sent out
     *
     * @ApiDoc(
     *   section = "Authentication",
     *   description = "Reset password request",
     *   statusCodes = {
     *     204 = "Reset password email sent",
     *     400 = "Email address invalid or doesn't exist in the system",
     *     401 = "Authentication required"
     *   }
     * )
     *
     * @Rest\Post("/request", name="reset_password_request")
     * @Rest\RequestParam(
     *   name = "email",
     *   description = "Email of the user requesting the password reset",
     *   requirements = ".+",
     *   strict = true
     * )
     *
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return View
     */
    public function requestPasswordResetEmailAction(ParamFetcherInterface $paramFetcher)
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
        if (!$user->isEnabled()) {
            return View::create(
                [
                    'error' => 'This account has been disabled.',
                ],
                403
            );
        }

        // generate the token and send the email
        $userManager->generatePasswordResetToken($user);

        return View::create(null, 204);
    }

    /**
     * Reset a user's password
     *
     * @ApiDoc(
     *   section = "Authentication",
     *   description = "Reset password",
     *   input = "SnapRapid\ApiBundle\Form\ResetPasswordType",
     *   statusCodes = {
     *     200 = "Password reset successfully and user is logged in",
     *     400 = "Password not valid or token expired",
     *     401 = "Authentication required"
     *   }
     * )
     *
     * @Rest\Post("/reset", name="api_reset_password")
     * @Rest\RequestParam(
     *   name = "password",
     *   description = "New password",
     *   array = true,
     *   strict = true
     * )
     * @Rest\RequestParam(
     *   name = "token",
     *   description = "Password reset token",
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
    public function resetPasswordAction(ParamFetcherInterface $paramFetcher, Request $request)
    {
        $userManager = $this->get('user_manager');
        $user        = $userManager->findUserByResetPasswordToken($paramFetcher->get('token'));

        // validate using forms - note form updates user object with incoming password and token
        $form = $this->createForm(
            new ResetPasswordType(),
            $user
        );
        $form->handleRequest($request);

        if ($form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $userManager->resetPassword($user, $newPassword);

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
     * Get the roles for the active user
     *
     * @ApiDoc(
     *   section = "Authentication",
     *   description = "Get user roles",
     *   statusCodes = {
     *     200 = "Roles returned",
     *     401 = "Not authenticated"
     *   }
     * )
     *
     * @Rest\Get("/roles", name="get_roles")
     * @Rest\View
     *
     * @Security("has_role('ROLE_USER')")
     *
     * @return View
     */
    public function getRoles()
    {
        $user = $this->getUser();

        if (in_array(
            $user->getRole(),
            [User::ROLE_CONTENT_MANAGER, User::ROLE_CONTENT_CURATOR_KEYWORDS, User::ROLE_CONTENT_CURATOR_LOGOS]
        )) {
            return [
                'role'             => $user->getRole(),
                'labels'           => $user->getApiAccessLabels(),
                'date_range_start' => $user->getApiAccessDateRangeStart(),
                'date_range_end'   => $user->getApiAccessDateRangeEnd(),
            ];
        }

        return [
            'role' => $user->getRole(),
        ];
    }
}
