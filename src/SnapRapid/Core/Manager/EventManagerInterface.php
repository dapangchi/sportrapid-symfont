<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\Event;

interface EventManagerInterface
{
    public function createNewEvent();
    public function saveNewEvent(Event $event);
    public function updateEvent(Event $event);
    public function removeEvent(Event $event);
    public function addChildEvents(Event $event, Company $company = null);
    public function canCompanyViewEvent(Company $company, Event $event);
    public function decorateEvent(Event $event);
    public function getEvents($order, $offset, $limit);
}
