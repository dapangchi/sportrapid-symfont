<?php

namespace SnapRapid\ApiBundle\Repository;

use SnapRapid\ApiBundle\Repository\Base\PersistableDocumentRepository;
use SnapRapid\Core\Repository\NotificationRepositoryInterface;

class NotificationRepository extends PersistableDocumentRepository implements NotificationRepositoryInterface
{
    protected function getEntityName()
    {
        return 'SnapRapidApiBundle:Notification';
    }
}
