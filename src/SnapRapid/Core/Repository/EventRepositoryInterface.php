<?php

namespace SnapRapid\Core\Repository;

use SnapRapid\Core\Model\Event;
use SnapRapid\Core\Repository\Base\PageableModelInterface;
use SnapRapid\Core\Repository\Base\PersistentModelRepositoryInterface;

interface EventRepositoryInterface extends
    PersistentModelRepositoryInterface,
    PageableModelInterface
{
    public function getDescendantEventIds(Event $event);
    public function getChildEvents(Event $event);
}
