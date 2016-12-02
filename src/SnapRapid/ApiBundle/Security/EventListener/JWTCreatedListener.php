<?php

namespace SnapRapid\ApiBundle\Security\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JWTCreatedListener
{
    /**
     * @param JWTCreatedEvent $event
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        // add the user id to the jwt payload
        $payload      = $event->getData();
        $securityUser = $event->getUser();
        if (get_class($securityUser) == 'SnapRapid\ApiBundle\Security\User\SecurityUser' && $securityUser->getId()) {
            $payload['uid'] = $securityUser->getId();
        }

        $event->setData($payload);
    }
}
