<?php

namespace SnapRapid\Core\EventListener;

use SnapRapid\Core\Event\CompanyEvent;
use SnapRapid\Core\Mailer\CompanyMailerInterface;

class CompanyEventListener
{
    /**
     * @var CompanyMailerInterface
     */
    private $mailer;

    /**
     * @param CompanyMailerInterface $mailer
     */
    public function __construct(CompanyMailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * New company created
     *
     * @param CompanyEvent $companyEvent
     */
    public function onCreate(CompanyEvent $companyEvent)
    {
    }

    /**
     * Company is updated
     *
     * @param CompanyEvent $companyEvent
     */
    public function onUpdate(CompanyEvent $companyEvent)
    {
    }

    /**
     * Company is deleted
     *
     * @param CompanyEvent $companyEvent
     */
    public function onRemove(CompanyEvent $companyEvent)
    {
    }
}
