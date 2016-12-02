<?php

namespace SnapRapid\Core\EventListener;

use SnapRapid\Core\Event\UserEvent;
use SnapRapid\Core\Mailer\UserMailerInterface;

class UserEventListener
{
    /**
     * @var UserMailerInterface
     */
    private $mailer;

    /**
     * @param UserMailerInterface $mailer
     */
    public function __construct(UserMailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * New user created
     *
     * @param UserEvent $userEvent
     */
    public function onCreate(UserEvent $userEvent)
    {
        $this->mailer->sendAccountActivationEmail(
            $userEvent->getUser()
        );
    }

    /**
     * Account activation resend requested
     *
     * @param UserEvent $userEvent
     */
    public function onActivationResendRequested(UserEvent $userEvent)
    {
        $this->mailer->sendAccountActivationEmail(
            $userEvent->getUser()
        );
    }

    /**
     * New user activated
     *
     * @param UserEvent $userEvent
     */
    public function onActivate(UserEvent $userEvent)
    {
        $this->mailer->sendAccountCreatedEmail(
            $userEvent->getUser()
        );
    }

    /**
     * User updates details
     *
     * @param UserEvent $userEvent
     */
    public function onUpdate(UserEvent $userEvent)
    {
    }

    /**
     * User removes their account
     *
     * @param UserEvent $userEvent
     */
    public function onRemove(UserEvent $userEvent)
    {
        $this->mailer->sendAccountRemovedEmail(
            $userEvent->getUser()
        );
    }

    /**
     * User logs into the site
     *
     * @param UserEvent $userEvent
     */
    public function onLogIn(UserEvent $userEvent)
    {
    }

    /**
     * Password reset is requested
     *
     * @param UserEvent $userEvent
     */
    public function onPasswordResetRequested(UserEvent $userEvent)
    {
        $this->mailer->sendPasswordResetRequestEmail(
            $userEvent->getUser()
        );
    }

    /**
     * Password is reset
     *
     * @param UserEvent $userEvent
     */
    public function onPasswordReset(UserEvent $userEvent)
    {
        $this->mailer->sendPasswordResetEmail(
            $userEvent->getUser()
        );
    }
}
