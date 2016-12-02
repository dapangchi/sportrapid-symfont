<?php

namespace SnapRapid\Core\Event;

use SnapRapid\Core\Model\Event;
use Symfony\Component\EventDispatcher\Event as TrueEvent;

class EventEvent extends TrueEvent
{
    /**
     * @var Event
     */
    protected $event;

    /**
     * @param Event $event
     */
    public function __construct(Event $event = null)
    {
        $this->event = $event;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }
}
