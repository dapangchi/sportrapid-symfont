<?php

namespace SnapRapid\ApiBundle\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class IncorrectLoginMethodException extends AuthenticationException implements HttpStatusAuthenticationExceptionInterface
{
    /**
     * @return int
     */
    public function getHttpStatus()
    {
        return 401; // 401 Unauthorized
    }
}
