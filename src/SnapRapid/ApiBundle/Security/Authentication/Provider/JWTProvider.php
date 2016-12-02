<?php

namespace SnapRapid\ApiBundle\Security\Authentication\Provider;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Provider\JWTProvider as BaseJWTProvider;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class JWTProvider
 * This extends the Lexik JWTProvider to allow for authentication of API tokens (anonymous)
 */
class JWTProvider extends BaseJWTProvider
{
    /**
     * @param TokenInterface $token
     *
     * @return JWTUserToken
     */
    public function authenticate(TokenInterface $token)
    {
        $payload = $this->jwtManager->decode($token);
        if (!$payload) {
            throw new AuthenticationException('Invalid JWT Token');
        }

        $user      = $this->getUserFromPayload($payload);
        $authToken = new JWTUserToken($user->getRoles());
        $authToken->setUser($user);

        return $authToken;
    }

    /**
     * @param array $payload
     *
     * @return mixed
     */
    protected function getUserFromPayload(array $payload)
    {
        return $this->userProvider->loadUserById($payload['uid']);
    }
}
