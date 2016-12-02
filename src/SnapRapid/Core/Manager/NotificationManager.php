<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Event\NotificationEvent;
use SnapRapid\Core\Events\CompanyEvents;
use SnapRapid\Core\Exception\CoreException;
use SnapRapid\Core\Exception\InvalidArgumentsException;
use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Model\Notification;
use SnapRapid\Core\Model\User;
use SnapRapid\Core\Repository\NotificationRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NotificationManager implements NotificationManagerInterface
{
    /**
     * @var NotificationRepositoryInterface
     */
    private $notificationRepository;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param NotificationRepositoryInterface $notificationRepository
     * @param EventDispatcherInterface        $dispatcher
     */
    public function __construct(
        NotificationRepositoryInterface $notificationRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->dispatcher             = $dispatcher;
    }

    /**
     * Setter injection of UserManager to prevent circular ref
     *
     * @param UserManagerInterface $userManager
     */
    public function setUserManager(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Handle the notification response
     *
     * @param Notification $notification
     * @param              $response
     *
     * @return int
     */
    public function handleNotificationResponse(Notification $notification, $response)
    {
        // check response is ok
        if ($response !== Notification::POSITIVE_RESPONSE && $response !== Notification::NEGATIVE_RESPONSE) {
            throw new InvalidArgumentsException('Notification response is not valid.');
        }

        // update notification with response
        $notification->setResponse($response);
        $this->notificationRepository->save($notification);

        // remove notification
        $notification->getUser()->removeNotification($notification);
        $this->notificationRepository->remove($notification);

        // dispatch response event
        $notificationEvent = new NotificationEvent($notification);
        if ($notification->getResponseEvent()) {
            $this->dispatcher->dispatch(
                $notification->getResponseEvent(),
                $notificationEvent
            );

            if ($notificationEvent->getStatus() == NotificationEvent::STATUS_ERROR) {
                throw new CoreException($notificationEvent->getMessage());
            }
        }

        // return message
        return $notificationEvent->getMessage() ?: 0;
    }

    /**
     * Create a new company member invite notification
     *
     * @param CompanyMember $companyMember
     * @param User          $user
     * 
     * @return Notification
     */
    public function createCompanyMemberInviteNotification(CompanyMember $companyMember, User $user)
    {
        $notification = new Notification();
        $notification
            ->setUser($user)
            ->setMessage($companyMember->getCompany()->getName().' wants to add you as a member.')
            ->setPositiveResponseBtn('Accept')
            ->setNegativeResponseBtn('Decline')
            ->setResponseEvent(CompanyEvents::MEMBER_INVITE_RESPONDED_TO)
            ->setRelatedObjectId($companyMember->getId());
        $this->notificationRepository->save($notification);

        return $notification;
    }
}
