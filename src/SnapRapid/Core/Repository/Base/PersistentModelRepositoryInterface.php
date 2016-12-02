<?php

namespace SnapRapid\Core\Repository\Base;

use SnapRapid\Core\Model\Base\PersistentModel;

interface PersistentModelRepositoryInterface
{
    /**
     * @param int $id
     *
     * @return PersistentModel
     */
    public function get($id);

    /**
     * @param array $ids
     *
     * @return PersistentModel[]
     */
    public function getMultiple(array $ids);

    /**
     * @param array $criteria
     *
     * @return PersistentModel
     */
    public function findOneBy(array $criteria);

    /**
     * @param array $criteria
     *
     * @return PersistentModel[]
     */
    public function search(array $criteria);

    /**
     * @param PersistentModel $object
     */
    public function save(PersistentModel $object);

    /**
     * @param PersistentModel $object
     */
    public function remove(PersistentModel $object);

    /**
     * @param PersistentModel $object
     */
    public function refresh(PersistentModel $object);
}
