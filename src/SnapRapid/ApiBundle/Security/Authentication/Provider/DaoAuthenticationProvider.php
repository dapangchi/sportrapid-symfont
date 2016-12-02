<?php

namespace SnapRapid\ApiBundle\Security\Authentication\Provider;

use SnapRapid\ApiBundle\Security\Exception\AccountDisabledException;
use SnapRapid\ApiBundle\Security\User\SecurityUser;
use Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider as BaseDaoAuthenticationProvider;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

class DaoAuthenticationProvider extends BaseDaoAuthenticationProvider
{
    /**
     * {@inheritdoc}
     *
     * @var SecurityUser
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        parent::checkAuthentication($user, $token);

        if (!$user->getUser()->isEnabled()) {
            throw new AccountDisabledException('This account has been disabled.');
        }
    }
}
