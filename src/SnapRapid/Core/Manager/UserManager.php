<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Event\CompanyMemberEvent;
use SnapRapid\Core\Event\UserEvent;
use SnapRapid\Core\Events\CompanyEvents;
use SnapRapid\Core\Events\UserEvents;
use SnapRapid\Core\Exception\InvalidEntityException;
use SnapRapid\Core\Model\User;
use SnapRapid\Core\Repository\CompanyRepositoryInterface;
use SnapRapid\Core\Repository\UserRepositoryInterface;
use SnapRapid\Core\Util\Canonicalizer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserManager implements UserManagerInterface
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var Canonicalizer
     */
    private $canonicalizer;

    /**
     * @var CompanyManagerInterface
     */
    private $companyManager;

    /**
     * @param UserRepositoryInterface    $userRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param EventDispatcherInterface   $dispatcher
     * @param Canonicalizer              $canonicalizer
     */
    public function __construct(
        UserRepositoryInterface $userRepository,
        CompanyRepositoryInterface $companyRepository,
        EventDispatcherInterface $dispatcher,
        Canonicalizer $canonicalizer
    ) {
        $this->userRepository    = $userRepository;
        $this->companyRepository = $companyRepository;
        $this->dispatcher        = $dispatcher;
        $this->canonicalizer     = $canonicalizer;
    }

    /**
     * Setter injection of CompanyManager to prevent circular ref
     *
     * @param CompanyManagerInterface $companyManager
     */
    public function setCompanyManager(CompanyManagerInterface $companyManager)
    {
        $this->companyManager = $companyManager;
    }

    /**
     * Create a new User object
     *
     * @param User $createdBy
     *
     * @return User
     */
    public function createNewUser(User $createdBy = null)
    {
        $user = new User();
        $user->addRole(User::ROLE_USER);

        return $user;
    }

    /**
     * Save the new User
     *
     * @param User $user
     * @param User $activeUser
     */
    public function saveNewUser(User $user, User $activeUser = null)
    {
        $this->updateCanonicalFields($user);
        $this->setUserRole($user);

        // if this user is being created by another then they need an activation token
        if ($activeUser) {
            $this->setAccountActivationToken($user);
            $this->userRepository->save($user);

            $this->dispatcher->dispatch(
                UserEvents::USER_ACCOUNT_CREATED,
                new UserEvent($user)
            );
        } else {
            $this->userRepository->detachBlameableSubscriber();

            // invited user so add the company role to them
            $companyMember = $this->companyRepository->findMemberByInvitationToken($user->getInvitationToken());
            if (!$companyMember) {
                throw new InvalidEntityException('User cannot be created without a company invitation.');
            }
            $user->addCompanyMemberRole($companyMember);
            $this->userRepository->save($user);

            // update company and company member
            $company = $companyMember->getCompany();
            $companyMember
                ->setUser($user)
                ->setAcceptedAt(new \DateTime())
                ->setInvitationToken(null);
            $this->companyRepository->save($company);

            $this->dispatcher->dispatch(
                CompanyEvents::MEMBER_INVITE_ACCEPTED,
                new CompanyMemberEvent($companyMember)
            );
        }
    }

    /**
     * Update canonical fields
     *
     * @param User $user
     */
    public function updateCanonicalFields(User $user)
    {
        $user->setEmailCanonical($this->canonicalizer->canonicalize($user->getEmail()));
    }

    /**
     * Update an existing User
     *
     * @param User $user
     */
    public function updateUser(User $user)
    {
        $this->updateCanonicalFields($user);
        $this->setUserRole($user);

        $this->userRepository->save($user);

        $this->dispatcher->dispatch(
            UserEvents::USER_ACCOUNT_UPDATED,
            new UserEvent($user)
        );
    }

    /**
     * Sets or updates the user role
     *
     * @param User $user
     */
    protected function setUserRole(User $user)
    {
        if ($user->getRole() && in_array($user->getRole(),
                [
                    User::ROLE_ADMIN,
                    User::ROLE_CONTENT_MANAGER,
                    User::ROLE_CONTENT_CURATOR_LOGOS,
                    User::ROLE_CONTENT_CURATOR_KEYWORDS,
                    User::ROLE_USER,
                ]
            )
        ) {
            $user->setRoles([$user->getRole()]);
        } else {
            $user->setRoles([User::ROLE_USER]);
        }
    }

    /**
     * Generate a password reset token for a user
     *
     * @param User $user
     */
    public function generatePasswordResetToken(User $user)
    {
        if (!$user->getPasswordResetToken()) {
            $user->setPasswordResetToken(md5(uniqid(mt_rand(), true)));
        }
        $user->setPasswordResetTokenExpiresAt(new \DateTime('+1 day'));

        $this->userRepository->detachBlameableSubscriber();
        $this->userRepository->save($user);

        $this->dispatcher->dispatch(
            UserEvents::USER_PASSWORD_RESET_REQUESTED,
            new UserEvent($user)
        );
    }

    /**
     * Reset a user's password
     *
     * @param User $user
     * @param      $newPassword
     */
    public function resetPassword(User $user, $newPassword)
    {
        $this->userRepository->detachBlameableSubscriber();
        $user
            ->setPlainPassword($newPassword)
            ->setPasswordResetToken(null)
            ->setPasswordResetTokenExpiresAt(null);
        $this->userRepository->save($user);

        $this->dispatcher->dispatch(
            UserEvents::USER_PASSWORD_RESET,
            new UserEvent($user)
        );
    }

    /**
     * Generate an account activation token for a user
     *
     * @param User $user
     */
    public function setAccountActivationToken(User $user)
    {
        $user->setAccountActivationToken(hash('sha256', uniqid(mt_rand(), true)));
    }

    /**
     * Resend account activation
     *
     * @param User $user
     */
    public function resendAccountActivation(User $user)
    {
        $this->dispatcher->dispatch(
            UserEvents::USER_ACCOUNT_ACTIVATION_RESEND_REQUESTED,
            new UserEvent($user)
        );
    }

    /**
     * Activate a users account
     *
     * @param User   $user
     * @param string $password
     */
    public function activateAccount(User $user, $password)
    {
        $user
            ->setPlainPassword($password)
            ->setAccountActivationToken(null)
            ->setActivatedAt(new \DateTime());
        $this->userRepository->detachBlameableSubscriber();
        $this->userRepository->save($user);

        $this->dispatcher->dispatch(
            UserEvents::USER_ACCOUNT_ACTIVATED,
            new UserEvent($user)
        );
    }

    /**
     * Remove an existing User
     *
     * @param User $user
     */
    public function removeUser(User $user)
    {
        // set canonical email field to null so they can be reused
        $user->setEmailCanonical(null);
        $this->userRepository->save($user);

        // remove this user from any companies they were a member of
        foreach ($user->getCompanyMemberRoles() as $companyMemberRole) {
            $companyMemberRole->getCompany()->removeMember($companyMemberRole);
        }

        // remove the user
        $this->userRepository->remove($user);

        $this->dispatcher->dispatch(
            UserEvents::USER_ACCOUNT_REMOVED,
            new UserEvent($user)
        );
    }

    /**
     * Finds a user by email
     *
     * @param string $email
     *
     * @return User
     */
    public function findUserByEmail($email)
    {
        return $this->userRepository->findOneBy(
            [
                'emailCanonical' => $this->canonicalizer->canonicalize($email),
            ]
        );
    }

    /**
     * Find a user by id
     *
     * @param string $id
     *
     * @return User
     */
    public function findUserById($id)
    {
        return $this->userRepository->get($id);
    }

    /**
     * Finds a user by a reset password token
     *
     * @param string $token
     *
     * @return User
     */
    public function findUserByResetPasswordToken($token)
    {
        return $this->userRepository->findOneBy(
            [
                'passwordResetToken' => $token,
            ]
        );
    }

    /**
     * Finds a user by account activation token
     *
     * @param string $token
     *
     * @return User
     */
    public function findUserByAccountActivationToken($token)
    {
        return $this->userRepository->findOneBy(
            [
                'accountActivationToken' => $token,
            ]
        );
    }

    /**
     * Decorate user object
     *
     * @param User $user
     * @param bool $isSelf
     */
    public function decorateUser(User $user, $isSelf = false)
    {
        foreach ($user->getCompanyMemberRoles() as $companyMemberRole) {
            $companyMemberRole->setCompany(
                $this->companyManager->decorateCompany(
                    $companyMemberRole->getCompany()
                )
            );
        }
    }
}
