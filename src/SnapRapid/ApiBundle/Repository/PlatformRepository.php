<?php

namespace SnapRapid\ApiBundle\Repository;

use Doctrine\ODM\MongoDB\Query\Builder;
use SnapRapid\ApiBundle\Repository\Base\ReadOnlyDocumentRepository;
use SnapRapid\Core\Repository\PlatformRepositoryInterface;

class PlatformRepository extends ReadOnlyDocumentRepository implements PlatformRepositoryInterface
{
    use Traits\PageableEntity;

    /**
     * Get results query builder
     *
     * @param array $filters
     * @param array $sorting
     *
     * @return Builder
     */
    public function getResultsQueryBuilder(array $filters = [], array $sorting = [])
    {
        /** @var Builder $qb */
        $qb = $this->documentRepository->createQueryBuilder($this->getEntityName());

        // add filters
        foreach ($filters as $field => $value) {
            $qb->addAnd(
                $qb->expr()
                    ->field($field)
                    ->equals(new \MongoRegex('/.*'.$value.'.*/i'))
            );
        }

        // set ordering
        foreach ($sorting as $field => $order) {
            $qb->sort($field, $order);
        }

        return $qb;
    }

    protected function getEntityName()
    {
        return 'SnapRapidApiBundle:Platform';
    }
}
