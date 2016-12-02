<?php

namespace SnapRapid\Core\EventListener;

use SnapRapid\Core\Event\CompanyMemberEvent;
use SnapRapid\Core\Event\NotificationEvent;
use SnapRapid\Core\Exception\ConflictException;
use SnapRapid\Core\Mailer\CompanyMemberMailerInterface;
use SnapRapid\Core\Manager\CompanyManagerInterface;
use SnapRapid\Core\Model\Notification;

class CompanyMemberEventListener
{
    /**
     * @var CompanyManagerInterface
     */
    private $companyManager;

    /**
     * @var CompanyMemberMailerInterface
     */
    private $mailer;

    /**
     * @param CompanyManagerInterface      $companyManager
     * @param CompanyMemberMailerInterface $mailer
     */
    public function __construct(CompanyManagerInterface $companyManager, CompanyMemberMailerInterface $mailer)
    {
        $this->companyManager = $companyManager;
        $this->mailer         = $mailer;
    }

    /**
     * Add a member
     *
     * @param CompanyMemberEvent $companyMemberEvent
     */
    public function onAddMember(CompanyMemberEvent $companyMemberEvent)
    {
        $this->mailer->sendMemberAddedEmail(
            $companyMemberEvent->getCompanyMember()
        );
    }

    /**
     * Update a member
     *
     * @param CompanyMemberEvent $companyMemberEvent
     */
    public function onUpdateMember(CompanyMemberEvent $companyMemberEvent)
    {
    }

    /**
     * Response is made to an company member invite
     *
     * @param NotificationEvent $notificationEvent
     */
    public function onResponseToInvite(NotificationEvent $notificationEvent)
    {
        $notification = $notificationEvent->getNotification();
        try {
            if ($notification->getResponse() == Notification::POSITIVE_RESPONSE) {
                $successMessage = $this->companyManager->acceptCompanyMemberInvite(
                    $notification->getRelatedObjectId(),
                    $notification->getUser()
                );
                $notificationEvent
                    ->setStatus(NotificationEvent::STATUS_SUCCESS)
                    ->setMessage($successMessage);
            } elseif ($notification->getResponse() == Notification::NEGATIVE_RESPONSE) {
                $successMessage = $this->companyManager->declineCompanyMemberInvite(
                    $notification->getRelatedObjectId(),
                    $notification->getUser()
                );
                $notificationEvent
                    ->setStatus(NotificationEvent::STATUS_SUCCESS)
                    ->setMessage($successMessage);
            }
        } catch (ConflictException $e) {
            $notificationEvent
                ->setStatus(NotificationEvent::STATUS_ERROR)
                ->setMessage($e->getMessage());
        }
    }

    /**
     * Member accepts invite
     *
     * @param CompanyMemberEvent $companyMemberEvent
     */
    public function onAcceptInvite(CompanyMemberEvent $companyMemberEvent)
    {
        $this->mailer->sendInviteAcceptedEmails(
            $companyMemberEvent->getCompanyMember()
        );
    }

    /**
     * Member declines invite
     *
     * @param CompanyMemberEvent $companyMemberEvent
     */
    public function onDeclineInvite(CompanyMemberEvent $companyMemberEvent)
    {
        $this->mailer->sendInviteDeclinedEmails(
            $companyMemberEvent->getCompanyMember()
        );
    }

    /**
     * Resend invitation emails
     *
     * @param CompanyMemberEvent $companyMemberEvent
     */
    public function onInviteResendRequested(CompanyMemberEvent $companyMemberEvent)
    {
        $this->mailer->sendMemberAddedEmail(
            $companyMemberEvent->getCompanyMember()
        );
    }

    /**
     * Member is removed
     *
     * @param CompanyMemberEvent $companyMemberEvent
     */
    public function onRemoveMember(CompanyMemberEvent $companyMemberEvent)
    {
        $this->mailer->sendMemberRemovedEmails(
            $companyMemberEvent->getCompanyMember()
        );
    }
}
