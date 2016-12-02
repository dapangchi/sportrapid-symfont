<?php

namespace SnapRapid\ApiBundle\Repository\Base;

use SnapRapid\Core\Model\Base\PersistentModel;

abstract class ReadOnlyDocumentRepository extends DocumentRepository
{
    /**
     * @param PersistentModel $object
     *
     * @return bool|void
     */
    public function save(PersistentModel $object)
    {
        if (!$object->isNew()) {
            throw new \InvalidArgumentException('Entity is read only and cannot be updated.');
        }
        $this->documentManager->persist($object);
        $this->documentManager->flush();
    }
}
