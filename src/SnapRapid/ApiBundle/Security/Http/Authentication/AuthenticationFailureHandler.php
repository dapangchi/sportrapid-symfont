<?php

namespace SnapRapid\ApiBundle\Security\Http\Authentication;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler as BaseAuthenticationFailureHandler;
use SnapRapid\ApiBundle\Security\Exception\HttpStatusAuthenticationExceptionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationFailureHandler extends BaseAuthenticationFailureHandler
{
    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if (!($exception instanceof HttpStatusAuthenticationExceptionInterface)) {
            return parent::onAuthenticationFailure($request, $exception);
        }

        $data = [
            'code'    => $exception->getHttpStatus(),
            'message' => $exception->getMessage(),
        ];

        $event = new AuthenticationFailureEvent($request, $exception);
        $event->setResponse(new JsonResponse($data, $data['code']));

        $this->dispatcher->dispatch(Events::AUTHENTICATION_FAILURE, $event);

        return $event->getResponse();
    }
}
