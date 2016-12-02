<?php

namespace SnapRapid\ApiBundle\Repository\Base;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Repository\Base\PersistentModelRepositoryInterface;

abstract class DocumentRepository implements PersistentModelRepositoryInterface
{
    /**
     * @var ObjectManager
     */
    protected $documentManager;

    /**
     * @var ObjectRepository
     */
    protected $documentRepository;

    /**
     * @param ObjectManager $documentManager
     */
    public function __construct(ObjectManager $documentManager)
    {
        $this->documentManager    = $documentManager;
        $this->documentRepository = $documentManager->getRepository(
            $this->getEntityName()
        );
    }

    /**
     * @return string
     */
    abstract protected function getEntityName();

    /**
     * @param int $id
     *
     * @return PersistentModel
     */
    public function get($id)
    {
        return $this
            ->documentRepository
            ->find($id);
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function getMultiple(array $ids)
    {
        $criteria = ['id' => $ids];

        return $this->documentRepository->findBy($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return null|PersistentModel
     */
    public function findOneBy(array $criteria)
    {
        return $this
            ->documentRepository
            ->findOneBy($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return array
     */
    public function search(array $criteria)
    {
        return $this
            ->documentRepository
            ->findBy($criteria);
    }

    /**
     * @param PersistentModel $object
     */
    public function remove(PersistentModel $object)
    {
        $this->documentManager->remove($object);
        $this->documentManager->flush();
    }

    /**
     * @param PersistentModel $object
     */
    public function refresh(PersistentModel $object)
    {
        $this->documentManager->refresh($object);
    }
}
