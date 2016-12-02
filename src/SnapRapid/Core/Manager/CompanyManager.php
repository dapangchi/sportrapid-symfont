<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Event\CompanyEvent;
use SnapRapid\Core\Event\CompanyMemberEvent;
use SnapRapid\Core\Events\CompanyEvents;
use SnapRapid\Core\Exception\ConflictException;
use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Model\User;
use SnapRapid\Core\Repository\CompanyRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CompanyManager implements CompanyManagerInterface
{
    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @var NotificationManagerInterface
     */
    private $notificationManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param CompanyRepositoryInterface $companyRepository
     * @param EventDispatcherInterface   $dispatcher
     */
    public function __construct(
        CompanyRepositoryInterface $companyRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->companyRepository = $companyRepository;
        $this->dispatcher        = $dispatcher;
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
     * Setter injection of EventManager to prevent circular ref
     *
     * @param EventManagerInterface $eventManager
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Setter injection of NotificationManager to prevent circular ref
     *
     * @param NotificationManagerInterface $notificationManager
     */
    public function setNotificationManager(NotificationManagerInterface $notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

    /**
     * Create a new Company object
     *
     * @param User $user
     *
     * @return Company
     */
    public function createNewCompany(User $user)
    {
        $company = new Company();

        return $company;
    }

    /**
     * Save the new Company
     *
     * @param Company $company
     * @param User    $user
     */
    public function saveNewCompany(Company $company, User $user)
    {
        $this->companyRepository->save($company);

        $this->dispatcher->dispatch(
            CompanyEvents::COMPANY_CREATED,
            new CompanyEvent($company, $user)
        );
    }

    /**
     * Update an existing company
     *
     * @param Company $company
     * @param Company $oldCompany
     * @param User    $user
     */
    public function updateCompany(Company $company, Company $oldCompany, User $user)
    {
        $this->companyRepository->save($company);

        $this->dispatcher->dispatch(
            CompanyEvents::COMPANY_UPDATED,
            new CompanyEvent($company, $user)
        );
    }

    /**
     * Remove an existing company
     *
     * @param Company $company
     * @param User    $user
     */
    public function removeCompany(Company $company, User $user)
    {
        $this->companyRepository->remove($company);

        $this->dispatcher->dispatch(
            CompanyEvents::COMPANY_REMOVED,
            new CompanyEvent($company, $user)
        );
    }

    /**
     * Decorate company object
     *
     * @param Company $company
     *
     * @return Company
     */
    public function decorateCompany(Company $company)
    {
        // build event stacks
        $eventStacks = [];
        foreach ($company->getEvents() as $event) {
            $this->companyRepository->refresh($event);
            $eventStack = [$event];
            while ($event->getParent()) {
                $event = $event->getParent();
                $this->companyRepository->refresh($event);
                $eventStack[] = $event;
            }
            foreach ($eventStack as &$event) {
                $this->eventManager->addChildEvents($event, $company);
                $event = clone $event;
            }
            $eventStacks[] = array_reverse($eventStack);
        }
        $company->setEventStacks($eventStacks);

        return $company;
    }

    /**
     * Save a new company member
     *
     * @param CompanyMember $companyMember
     */
    public function saveNewCompanyMember(CompanyMember $companyMember)
    {
        $user = $this->userManager->findUserByEmail($companyMember->getEmail());
        if ($user) {
            $companyMember->setMatchingUser($user);
        } else {
            $this->setInvitationToken($companyMember);
        }

        $company = $companyMember->getCompany();
        $this->companyRepository->save($company);

        if ($user) {
            $notification = $this->notificationManager->createCompanyMemberInviteNotification($companyMember, $user);
            $user->addNotification($notification);
            $this->userManager->updateUser($user);
        }

        $this->dispatcher->dispatch(
            CompanyEvents::MEMBER_ADDED,
            new CompanyMemberEvent($companyMember)
        );
    }

    /**
     * Update an company member
     *
     * @param CompanyMember $companyMember
     */
    public function updateCompanyMember(CompanyMember $companyMember)
    {
        $this->companyRepository->save($companyMember->getCompany());
        $this->dispatcher->dispatch(
            CompanyEvents::MEMBER_UPDATED,
            new CompanyMemberEvent($companyMember)
        );
    }

    /**
     * Remove member from an company
     *
     * @param CompanyMember $companyMember
     */
    public function removeCompanyMember(CompanyMember $companyMember)
    {
        $company = $companyMember->getCompany();
        $company->removeMember($companyMember);
        $user = $companyMember->getUser();
        if ($user) {
            $companyMember->getUser()->removeCompanyMemberRole($companyMember);
            $this->userManager->updateUser($user);

            $this->dispatcher->dispatch(
                CompanyEvents::MEMBER_REMOVED,
                new CompanyMemberEvent($companyMember)
            );
        }

        $this->companyRepository->save($company);
    }

    /**
     * Generate an invitation token for a user
     *
     * @param CompanyMember $member
     */
    public function setInvitationToken(CompanyMember $member)
    {
        $member->setInvitationToken(hash('sha256', uniqid(mt_rand(), true)));
    }

    /**
     * Resend invitation
     *
     * @param CompanyMember $member
     */
    public function resendInvitation(CompanyMember $member)
    {
        $this->dispatcher->dispatch(
            CompanyEvents::MEMBER_INVITE_RESEND_REQUESTED,
            new CompanyMemberEvent($member)
        );
    }

    /**
     * User accepts an invite to link their user account to the company member
     *
     * @param      $companyMemberId
     * @param User $user
     *
     * @return string
     */
    public function acceptCompanyMemberInvite($companyMemberId, User $user)
    {
        $companyMember = $this->getCompanyMemberInviteForUser($companyMemberId, $user);
        $company       = $companyMember->getCompany();
        $companyMember
            ->setUser($user)
            ->setAcceptedAt(new \DateTime())
            ->setMatchingUser(null)
            ->setEmail(null);
        $this->companyRepository->save($company);
        $user->addCompanyMemberRole($companyMember);
        $this->userManager->updateUser($user);

        $this->dispatcher->dispatch(
            CompanyEvents::MEMBER_INVITE_ACCEPTED,
            new CompanyMemberEvent($companyMember)
        );

        return 'You have been added as a member of "'.$company->getName().'".';
    }

    /**
     * User declines an invite to link their user account to the company member
     *
     * @param      $companyMemberId
     * @param User $user
     *
     * @return string
     */
    public function declineCompanyMemberInvite($companyMemberId, User $user)
    {
        $companyMember = $this->getCompanyMemberInviteForUser($companyMemberId, $user);
        $company       = $companyMember->getCompany();

        $company->removeMember($companyMember);
        $this->companyRepository->save($company);
        $user->removeCompanyMemberRole($companyMember);
        $this->userManager->updateUser($user);
        $this->companyRepository->remove($companyMember);

        $this->dispatcher->dispatch(
            CompanyEvents::MEMBER_INVITE_DECLINED,
            new CompanyMemberEvent($companyMember)
        );

        return 'You have declined the invitation to be a member of '.$company->getName().'.';
    }

    /**
     * Get the company member by id and check that it was intended for the given user
     *
     * @param      $companyMemberId
     * @param User $user
     *
     * @return CompanyMember
     */
    protected function getCompanyMemberInviteForUser($companyMemberId, User $user)
    {
        $companyMember = $this->companyRepository->getCompanyMemberById($companyMemberId);
        if (!$companyMember
            || $user->getEmail() != $companyMember->getEmail()
            || $companyMember->getUser()
            || $companyMember->getAcceptedAt()
        ) {
            throw new ConflictException(
                'The invite to join '.($companyMember ? $companyMember->getCompany()->getName() : '').' has expired.'
            );
        }

        return $companyMember;
    }
}
