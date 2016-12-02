<?php

namespace SnapRapid\ApiBundle\Security\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class BuildValidator
{
    /**
     * @var string
     */
    private $build;

    /**
     * BuildValidator constructor.
     *
     * @param $build
     */
    public function __construct($build)
    {
        $this->build = $build;
    }

    /**
     * Validate the client has the current build version
     *
     * @param GetResponseEvent $event
     */
    public function validateBuild(GetResponseEvent $event)
    {
        $clientBuild = $event->getRequest()->headers->get('Build');

        if ($clientBuild && $clientBuild != $this->build) {
            $event->setResponse(new Response(null, 412));
            $event->stopPropagation();
        }
    }
}
