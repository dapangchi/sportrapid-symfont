<?php

namespace SnapRapid\Core\Model;

use Gedmo\Blameable\Traits\Blameable;
use Gedmo\SoftDeleteable\Traits\SoftDeleteable;
use Gedmo\Timestampable\Traits\Timestampable;
use SnapRapid\ApiBundle\Security\User\SecurityUser;
use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Model\Collection\ArrayCollection;
use SnapRapid\Core\Model\Collection\Collection;

class User extends PersistentModel
{
    const ROLE_USER                     = 'ROLE_USER';
    const ROLE_CONTENT_MANAGER          = 'ROLE_CONTENT_MANAGER';
    const ROLE_CONTENT_CURATOR_KEYWORDS = 'ROLE_CONTENT_CURATOR_KEYWORDS';
    const ROLE_CONTENT_CURATOR_LOGOS    = 'ROLE_CONTENT_CURATOR_LOGOS';
    const ROLE_ADMIN                    = 'ROLE_ADMIN';

    use Timestampable,
        Blameable,
        SoftDeleteable;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $emailCanonical;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * The salt to use for hashing
     *
     * @var string
     */
    protected $salt;

    /**
     * Encrypted password. Must be persisted.
     *
     * @var string
     */
    protected $password;

    /**
     * Plain password. Used for model validation. Must not be persisted.
     *
     * @var string
     */
    protected $plainPassword;

    /**
     * Password reset token
     *
     * @var string
     */
    protected $passwordResetToken;

    /**
     * Password reset token expiration date
     *
     * @var \DateTime
     */
    protected $passwordResetTokenExpiresAt;

    /**
     * @var \DateTime
     */
    protected $lastLogin;

    /**
     * @var array
     */
    protected $roles = [];

    /**
     * @var string|null
     */
    protected $role;

    /**
     * @var bool|null
     */
    protected $isAdmin;

    /**
     * @var \DateTime|null
     */
    protected $apiAccessDateRangeStart = null;

    /**
     * @var \DateTime|null
     */
    protected $apiAccessDateRangeEnd = null;

    /**
     * @var string
     */
    protected $accountActivationToken = null;

    /**
     * @var \DateTime
     */
    protected $activatedAt = null;

    /**
     * @var Collection|CompanyMember[]
     */
    protected $companyMemberRoles;

    /**
     * @var Collection|Notification[]
     */
    protected $notifications;

    /**
     * @var Collection|Label[]
     */
    protected $apiAccessLabels;

    /**
     * Invitation token (unmapped)
     *
     * @var string
     */
    protected $invitationToken;

    public function __construct()
    {
        $this->salt                 = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->companyMemberRoles   = new ArrayCollection();
        $this->notifications        = new ArrayCollection();
        $this->apiAccessLabels      = new ArrayCollection();
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    /**
     * Check if a given security user is the same as this User instance
     *
     * @param SecurityUser $securityUser
     *
     * @return bool
     */
    public function isSameUserAs(SecurityUser $securityUser)
    {
        return $securityUser->getId() == $this->getId();
    }

    /**
     * @return bool
     */
    public function getIsAdmin()
    {
        if (!is_null($this->isAdmin)) {
            return $this->isAdmin;
        } else {
            return in_array(self::ROLE_ADMIN, $this->roles, true);
        }
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = strtolower($email);

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    /**
     * @param string $emailCanonical
     *
     * @return User
     */
    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     *
     * @return User
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordResetToken()
    {
        return $this->passwordResetToken;
    }

    /**
     * @param string $passwordResetToken
     *
     * @return User
     */
    public function setPasswordResetToken($passwordResetToken)
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPasswordResetTokenExpiresAt()
    {
        return $this->passwordResetTokenExpiresAt;
    }

    /**
     * @param \DateTime $passwordResetTokenExpiresAt
     *
     * @return User
     */
    public function setPasswordResetTokenExpiresAt($passwordResetTokenExpiresAt)
    {
        $this->passwordResetTokenExpiresAt = $passwordResetTokenExpiresAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     *
     * @return User
     */
    public function setLastLogin(\DateTime $lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     *
     * @return User
     */
    public function setRoles(array $roles)
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * @param $role
     *
     * @return $this
     */
    public function addRole($role)
    {
        $role = strtoupper($role);

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function getRole()
    {
        if (!is_null($this->role)) {
            return $this->role;
        } else {
            if (in_array(self::ROLE_ADMIN, $this->roles, true)) {
                return self::ROLE_ADMIN;
            } elseif (in_array(self::ROLE_CONTENT_MANAGER, $this->roles, true)) {
                return self::ROLE_CONTENT_MANAGER;
            } elseif (in_array(self::ROLE_CONTENT_CURATOR_KEYWORDS, $this->roles, true)) {
                return self::ROLE_CONTENT_CURATOR_KEYWORDS;
            } elseif (in_array(self::ROLE_CONTENT_CURATOR_LOGOS, $this->roles, true)) {
                return self::ROLE_CONTENT_CURATOR_LOGOS;
            } else {
                return self::ROLE_USER;
            }
        }
    }

    /**
     * @param null|string $role
     *
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName()
    {
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * @return Collection|CompanyMember[]
     */
    public function getCompanyMemberRoles()
    {
        return $this->companyMemberRoles;
    }

    /**
     * @param CompanyMember[] $companyMemberRoles
     *
     * @return User
     */
    public function setCompanyMemberRoles($companyMemberRoles)
    {
        $this->companyMemberRoles = $companyMemberRoles;

        return $this;
    }

    /**
     * @param CompanyMember $companyMemberRole
     *
     * @return User
     */
    public function addCompanyMemberRole(CompanyMember $companyMemberRole)
    {
        if (!$this->companyMemberRoles->contains($companyMemberRole)) {
            $this->companyMemberRoles->add($companyMemberRole);
        }

        return $this;
    }

    /**
     * @param CompanyMember $companyMemberRole
     *
     * @return User
     */
    public function removeCompanyMemberRole(CompanyMember $companyMemberRole)
    {
        if ($this->companyMemberRoles->contains($companyMemberRole)) {
            $this->companyMemberRoles->removeElement($companyMemberRole);
        }

        return $this;
    }

    /**
     * @return Collection|Notification[]
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @param Collection|Notification[] $notifications
     *
     * @return User
     */
    public function setNotifications($notifications)
    {
        $this->notifications = $notifications;

        return $this;
    }

    /**
     * @param Notification $notification
     *
     * @return User
     */
    public function addNotification(Notification $notification)
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
        }

        return $this;
    }

    /**
     * @param Notification $notification
     *
     * @return User
     */
    public function removeNotification(Notification $notification)
    {
        if ($this->notifications->contains($notification)) {
            $this->notifications->removeElement($notification);
        }

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getApiAccessDateRangeStart()
    {
        return $this->apiAccessDateRangeStart;
    }

    /**
     * @param \DateTime|null $apiAccessDateRangeStart
     *
     * @return Event
     */
    public function setApiAccessDateRangeStart($apiAccessDateRangeStart)
    {
        $this->apiAccessDateRangeStart = $apiAccessDateRangeStart;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getApiAccessDateRangeEnd()
    {
        return $this->apiAccessDateRangeEnd;
    }

    /**
     * @param \DateTime|null $apiAccessDateRangeEnd
     *
     * @return Event
     */
    public function setApiAccessDateRangeEnd($apiAccessDateRangeEnd)
    {
        $this->apiAccessDateRangeEnd = $apiAccessDateRangeEnd;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountActivationToken()
    {
        return $this->accountActivationToken;
    }

    /**
     * @param string $accountActivationToken
     *
     * @return $this
     */
    public function setAccountActivationToken($accountActivationToken)
    {
        $this->accountActivationToken = $accountActivationToken;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getActivatedAt()
    {
        return $this->activatedAt;
    }

    /**
     * @param \DateTime $activatedAt
     *
     * @return User
     */
    public function setActivatedAt(\DateTime $activatedAt = null)
    {
        $this->activatedAt = $activatedAt;

        return $this;
    }

    /**
     * Check if account is activated
     *
     * @return bool
     */
    public function isAccountActivated()
    {
        return isset($this->activatedAt);
    }

    /**
     * @return string
     */
    public function getInvitationToken()
    {
        return $this->invitationToken;
    }

    /**
     * @param string $invitationToken
     *
     * @return User
     */
    public function setInvitationToken($invitationToken)
    {
        $this->invitationToken = $invitationToken;

        return $this;
    }

    /**
     * @return Collection|Label[]
     */
    public function getApiAccessLabels()
    {
        return $this->apiAccessLabels;
    }

    /**
     * @param Label $apiAccessLabel
     *
     * @return Company
     */
    public function addApiAccessLabel($apiAccessLabel)
    {
        $this->apiAccessLabels->add($apiAccessLabel);

        return $this;
    }

    /**
     * @param Label $apiAccessLabel
     *
     * @return Company
     */
    public function removeApiAccessLabel($apiAccessLabel)
    {
        $this->apiAccessLabels->removeElement($apiAccessLabel);

        return $this;
    }
}
