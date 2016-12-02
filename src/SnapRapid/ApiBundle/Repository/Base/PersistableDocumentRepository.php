<?php

namespace SnapRapid\ApiBundle\Repository\Base;

use SnapRapid\Core\Model\Base\PersistentModel;

abstract class PersistableDocumentRepository extends DocumentRepository
{
    /**
     * @param PersistentModel $object
     */
    public function save(PersistentModel $object)
    {
        if ($object->isNew()) {
            $this->documentManager->persist($object);
        }
        $this->documentManager->flush();
    }
}
