<?php

namespace SnapRapid\ApiBundle\Security\Firewall;

use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class JWTAuthListener implements ListenerInterface
{
    /**
     * @var string
     */
    protected $providerKey;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var AuthenticationSuccessHandlerInterface
     */
    private $successHandler;

    /**
     * @var AuthenticationFailureHandlerInterface
     */
    private $failureHandler;

    /**
     * @param TokenStorageInterface                 $tokenStorage
     * @param AuthenticationManagerInterface        $authenticationManager
     * @param                                       $providerKey
     * @param AuthenticationSuccessHandlerInterface $successHandler
     * @param AuthenticationFailureHandlerInterface $failureHandler
     * @param array                                 $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        $providerKey,
        AuthenticationSuccessHandlerInterface $successHandler,
        AuthenticationFailureHandlerInterface $failureHandler,
        array $options = []
    ) {
        if (empty($providerKey)) {
            throw new InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage          = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->providerKey           = $providerKey;
        $this->successHandler        = $successHandler;
        $this->failureHandler        = $failureHandler;
        $this->options               = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // only allow post requests
        if (!$request->isMethod('POST')) {
            $event->setResponse(new JsonResponse('Invalid method', 405));

            return;
        }

        // see if we have username and password
        $username = trim($request->request->get($this->options['username_parameter']));
        $password = $request->request->get($this->options['password_parameter']);

        // get the appropriate token for the request
        if ($username || $password) {
            $token = new UsernamePasswordToken($username, $password, $this->providerKey);
        } else {
            $token = new JWTUserToken();
            $token->setAttribute('anon', true);
        }

        // authenticate the token
        try {
            $authenticatedToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authenticatedToken);
            $response = $this->successHandler->onAuthenticationSuccess($request, $authenticatedToken);
        } catch (AuthenticationException $e) {
            $response = $this->failureHandler->onAuthenticationFailure($request, $e);
        }

        $event->setResponse($response);
    }
}
