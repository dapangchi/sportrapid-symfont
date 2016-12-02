<?php

namespace SnapRapid\ApiBundle\Mailer;

use SnapRapid\Core\Mailer\UserMailerInterface;
use SnapRapid\Core\Model\User;

class UserMailer extends BaseMailer implements UserMailerInterface
{
    /**
     * Account created
     *
     * @param User $user
     */
    public function sendAccountCreatedEmail(User $user)
    {
        $context = ['user' => $user];

        $this->sendMessage(
            'SnapRapidApiBundle:Emails:User/account_created.eml.twig',
            $context,
            $user
        );
    }

    /**
     * Account activation
     *
     * @param User $user
     */
    public function sendAccountActivationEmail(User $user)
    {
        $context = ['user' => $user];

        $this->sendMessage(
            'SnapRapidApiBundle:Emails:User/account_activation.eml.twig',
            $context,
            $user
        );
    }

    /**
     * Account removed
     *
     * @param User $user
     */
    public function sendAccountRemovedEmail(User $user)
    {
        $context = ['user' => $user];

        $this->sendMessage(
            'SnapRapidApiBundle:Emails:User/account_removed.eml.twig',
            $context,
            $user
        );
    }

    /**
     * Password reset requested
     *
     * @param User $user
     */
    public function sendPasswordResetRequestEmail(User $user)
    {
        $context = ['user' => $user];

        $this->sendMessage(
            'SnapRapidApiBundle:Emails:User/password_reset_request.eml.twig',
            $context,
            $user
        );
    }

    /**
     * Password reset
     *
     * @param User $user
     */
    public function sendPasswordResetEmail(User $user)
    {
        $context = ['user' => $user];

        $this->sendMessage(
            'SnapRapidApiBundle:Emails:User/password_reset.eml.twig',
            $context,
            $user
        );
    }
}
