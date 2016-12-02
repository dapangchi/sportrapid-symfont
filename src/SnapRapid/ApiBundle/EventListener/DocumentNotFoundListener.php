<?php

namespace SnapRapid\ApiBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\DocumentNotFoundEventArgs;

class DocumentNotFoundListener implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            'documentNotFound',
        ];
    }

    /**
     * Catch proxy-document not found exceptions
     *
     * @param DocumentNotFoundEventArgs $eventArgs
     */
    public function documentNotFound(DocumentNotFoundEventArgs $eventArgs)
    {
        $om     = $eventArgs->getObjectManager();
        $object = $eventArgs->getObject();
        $class  = get_class($object);
        $meta   = $om->getClassMetadata($class);

        // suppress exceptions when authors are not found as we know we have bad data here
        if ($meta->name == 'SnapRapid\\Core\\Model\\Author') {
            $eventArgs->disableException();
        }
    }
}
