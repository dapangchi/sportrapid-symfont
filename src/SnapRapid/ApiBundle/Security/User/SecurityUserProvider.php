<?php

namespace SnapRapid\ApiBundle\Security\User;

use SnapRapid\Core\Manager\UserManager;
use SnapRapid\Core\Model\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SecurityUserProvider implements UserProviderInterface
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function loadUserByUsername($email)
    {
        // try to fetch the user from the database
        $user = $this->userManager->findUserByEmail($email);

        if ($user) {
            // inject the core user into the security user
            $securityUser = new SecurityUser($user);

            return $securityUser;
        }

        throw new UsernameNotFoundException(
            sprintf('Email adddress "%s" does not exist.', $email)
        );
    }

    /**
     * @param $id
     *
     * @return User
     */
    public function loadUserById($id)
    {
        // try to fetch the user from the database
        $user = $this->userManager->findUserById($id);

        if ($user) {
            // inject the core user into the security user
            $securityUser = new SecurityUser($user);

            return $securityUser;
        }

        throw new UsernameNotFoundException(
            sprintf('User ID "%s" does not exist.', $id)
        );
    }

    /**
     * @param UserInterface $user
     *
     * @return User
     */
    public function refreshUser(UserInterface $user)
    {
        // this is used for storing authentication in the session
        // but in this case, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless
        throw new UnsupportedUserException();
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === 'SnapRapid\Core\Model\User';
    }
}
