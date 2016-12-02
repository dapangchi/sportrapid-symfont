<?php

namespace SnapRapid\ApiBundle\Security\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AccountDisabledException extends AuthenticationException implements HttpStatusAuthenticationExceptionInterface
{
    /**
     * @return int
     */
    public function getHttpStatus()
    {
        return 403; // 403 Forbidden
    }
}
