<?php

namespace SnapRapid\ApiBundle\Security\Exception;

interface HttpStatusAuthenticationExceptionInterface
{
    /**
     * @return int
     */
    public function getHttpStatus();
}
