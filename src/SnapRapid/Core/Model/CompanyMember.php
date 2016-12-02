<?php

namespace SnapRapid\Core\Model;

use Gedmo\Blameable\Traits\Blameable;
use Gedmo\SoftDeleteable\Traits\SoftDeleteable;
use Gedmo\Timestampable\Traits\Timestampable;
use SnapRapid\Core\Model\Base\PersistentModel;

class CompanyMember extends PersistentModel
{
    const DEFAULT_ROLE = 'Administrator';

    use Timestampable,
        Blameable,
        SoftDeleteable;

    /**
     * @var Company
     */
    protected $company;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var User
     */
    protected $matchingUser;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var bool
     */
    protected $isAdmin = false;

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var string
     */
    protected $invitationToken = null;

    /**
     * @var \DateTime|null
     */
    protected $acceptedAt = null;

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     *
     * @return CompanyMember
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
        $this->company->addMember($this);

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return CompanyMember
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFirstName()
    {
        return $this->user ? $this->user->getFirstName() : null;
    }

    /**
     * @return null|string
     */
    public function getLastName()
    {
        return $this->user ? $this->user->getLastName() : null;
    }

    /**
     * @return User
     */
    public function getMatchingUser()
    {
        return $this->matchingUser;
    }

    /**
     * @param User $matchingUser
     *
     * @return CompanyMember
     */
    public function setMatchingUser(User $matchingUser = null)
    {
        $this->matchingUser = $matchingUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->user ? $this->user->getEmail() : $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return CompanyMember
     */
    public function setEmail($email)
    {
        $this->email = $email ? strtolower($email) : null;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @param bool $isAdmin
     * 
     * @return CompanyMember
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

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
    public function getInvitationToken()
    {
        return $this->invitationToken;
    }

    /**
     * @param string $invitationToken
     *
     * @return $this
     */
    public function setInvitationToken($invitationToken)
    {
        $this->invitationToken = $invitationToken;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getAcceptedAt()
    {
        return $this->acceptedAt;
    }

    /**
     * @param \DateTime|null $acceptedAt
     *
     * @return CompanyMember
     */
    public function setAcceptedAt($acceptedAt)
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }
}
