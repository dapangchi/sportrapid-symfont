<?php

namespace SnapRapid\ApiBundle\EventListener;

use Predis\Client;
use SnapRapid\Core\Event\CompanyEvent;
use SnapRapid\Core\Event\EventEvent;
use SnapRapid\Core\Manager\DashboardManager;
use SnapRapid\Core\Repository\EventRepositoryInterface;

class DashboardCacheEventListener
{
    /**
     * @var Client
     */
    private $redis;

    /**
     * @var EventRepositoryInterface
     */
    private $eventRepository;

    /**
     * @param Client                   $redis
     * @param EventRepositoryInterface $eventRepository
     */
    public function __construct(Client $redis, EventRepositoryInterface $eventRepository)
    {
        $this->redis           = $redis;
        $this->eventRepository = $eventRepository;
    }

    /**
     * When a company is updated or removed, remove its cache keys
     *
     * @param CompanyEvent $event
     */
    public function onUpdateCompany(CompanyEvent $event)
    {
        $keyOfKeys = DashboardManager::STORE_PREFIX_COMPANY_KEYS.$event->getCompany()->getId();
        $keys      = $this->redis->smembers($keyOfKeys);
        $keys[]    = $keyOfKeys;
        $this->redis->del($keys);
    }

    /**
     * When an event is updated or removed, remove its cache keys
     *
     * @param EventEvent $event
     */
    public function onUpdateEvent(EventEvent $event)
    {
        // note: horribly confusing usage of the word/var event
        $event = $event->getEvent();

        // get the event ids of all descendants and ancestors
        $eventIds    = [$event->getId()];
        $eventIds    = array_merge($eventIds, $this->eventRepository->getDescendantEventIds($event));
        $parentEvent = $event->getParent();
        while ($parentEvent) {
            $this->eventRepository->refresh($parentEvent);
            $eventIds[]  = $parentEvent->getId();
            $parentEvent = $parentEvent->getParent();
        }

        // remove all the caches for related events
        foreach ($eventIds as $eventId) {
            $keyOfKeys = DashboardManager::STORE_PREFIX_EVENT_KEYS.$eventId;
            $keys      = $this->redis->smembers($keyOfKeys);
            $keys[]    = $keyOfKeys;
            $this->redis->del($keys);
        }
    }
}
