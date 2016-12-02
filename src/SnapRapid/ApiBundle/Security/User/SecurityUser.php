<?php

namespace SnapRapid\ApiBundle\Security\User;

use SnapRapid\Core\Model\User;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityUser implements UserInterface
{
    /**
     * @var User
     */
    private $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the Core User object
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get the User id (convenience method)
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->user ? $this->user->getId() : null;
    }

    /// ----- UserInterface required methods passes straight to the Core User implementations -----
    public function getRoles()
    {
        return $this->user->getRoles();
    }
    public function getPassword()
    {
        return $this->user->getPassword();
    }
    public function getSalt()
    {
        return $this->user->getSalt();
    }
    public function getUsername()
    {
        return $this->user->getEmail();
    }
    public function eraseCredentials()
    {
        return $this->user->eraseCredentials();
    }
}
