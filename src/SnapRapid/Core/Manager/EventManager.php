<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Event\EventEvent;
use SnapRapid\Core\Events\EventEvents;
use SnapRapid\Core\Model\Collection\ArrayCollection;
use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\Event;
use SnapRapid\Core\Repository\EventRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventManager implements EventManagerInterface
{
    /**
     * @var EventRepositoryInterface
     */
    private $eventRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param EventRepositoryInterface $eventRepository
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EventRepositoryInterface $eventRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->eventRepository = $eventRepository;
        $this->dispatcher      = $dispatcher;
    }

    /**
     * Create a new Event object
     *
     * @return Event
     */
    public function createNewEvent()
    {
        $event = new Event();

        return $event;
    }

    /**
     * Save the new Event
     *
     * @param Event $event
     */
    public function saveNewEvent(Event $event)
    {
        $this->cleanDates($event);
        $this->eventRepository->save($event);

        $this->dispatcher->dispatch(
            EventEvents::EVENT_CREATED,
            new EventEvent($event)
        );
    }

    /**
     * Update an existing event
     *
     * @param Event $event
     */
    public function updateEvent(Event $event)
    {
        $this->cleanDates($event);
        $this->eventRepository->save($event);

        $this->dispatcher->dispatch(
            EventEvents::EVENT_UPDATED,
            new EventEvent($event)
        );
    }

    /**
     * Remove an existing event
     *
     * @param Event $event
     */
    public function removeEvent(Event $event)
    {
        $this->eventRepository->remove($event);

        $this->dispatcher->dispatch(
            EventEvents::EVENT_REMOVED,
            new EventEvent($event)
        );
    }

    /**
     * Add direct children to an event relating to a company
     * todo: see if this can be optimised
     *
     * @param Event   $event
     * @param Company $company
     */
    public function addChildEvents(Event $event, Company $company = null)
    {
        // if no company is passed then add all children to the event
        if (!$company) {
            $event->setChildren($this->eventRepository->getChildEvents($event));

            return;
        }

        $companyEvents = $company->getEvents();
        $descendantIds = $this->eventRepository->getDescendantEventIds($event);
        $children      = new ArrayCollection();
        foreach ($companyEvents as $companyEvent) {
            $this->eventRepository->refresh($companyEvent);

            // if the company can see this event then it can see all children, so stop here
            if ($companyEvent->getId() == $event->getId()) {
                $event->setChildren($this->eventRepository->getChildEvents($event));

                return;
            }

            // check if the event is an ancestor of the company event and add the direct child that builds the link
            if (in_array($companyEvent->getId(), $descendantIds)) {
                $childEvent         = clone $companyEvent;
                $parentCompanyEvent = $companyEvent->getParent();
                $this->eventRepository->refresh($parentCompanyEvent);
                while ($parentCompanyEvent && $parentCompanyEvent->getId() != $event->getId()) {
                    $childEvent         = clone $parentCompanyEvent;
                    $parentCompanyEvent = $parentCompanyEvent->getParent();
                    $this->eventRepository->refresh($companyEvent);
                }
                $children->add($childEvent);
            } else {
                // not descendant, so check if the company event is an ancestor of the event (ie, the reverse)
                $companyEventDescendantIds = $this->eventRepository->getDescendantEventIds($companyEvent);
                if (in_array($event->getId(), $companyEventDescendantIds)) {
                    // if the company can see any parent of this event then it can see all children, so stop here
                    $event->setChildren($this->eventRepository->getChildEvents($event));

                    return;
                }
            }
        }

        $event->setChildren($children);
    }

    /**
     * Check if a company can view a given event
     *
     * @param Company $company
     * @param Event   $event
     *
     * @return bool
     */
    public function canCompanyViewEvent(Company $company, Event $event)
    {
        $companyEvents   = $company->getEvents();
        $companyEventIds = [];
        foreach ($companyEvents as $companyEvent) {
            $companyEventIds[] = $companyEvent->getId();
        }

        // check if company is directly associated with this event
        if (in_array($event->getId(), $companyEventIds)) {
            return true;
        }

        // check if company is associated with a descendant of this event
        $descendantIds = $this->eventRepository->getDescendantEventIds($event);
        if (count(array_intersect($descendantIds, $companyEventIds))) {
            return true;
        }

        // check if the event is a descendant of one of the companies associated events
        foreach ($companyEvents as $companyEvent) {
            $descendantIds = $this->eventRepository->getDescendantEventIds($companyEvent);
            if (in_array($event->getId(), $descendantIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decorate event object
     *
     * @param Event $event
     */
    public function decorateEvent(Event $event)
    {
        // method probably not needed
    }

    /**
     * Get an array of event snippets
     *
     * @param string $order
     * @param int    $offset
     * @param int    $limit
     */
    public function getEvents($order, $offset, $limit)
    {
        return $this->eventRepository->getEventsForListing($order, $offset, $limit);
    }

    /**
     * Clean the date range fields if the 'custom' range is not selected
     *
     * @param Event $event
     */
    protected function cleanDates(Event $event)
    {
        if ($event->getDateRangeType() != Event::DATE_RANGE_TYPE_CUSTOM) {
            $event
                ->setDateRangeStart(null)
                ->setDateRangeEnd(null);
        }
    }
}
