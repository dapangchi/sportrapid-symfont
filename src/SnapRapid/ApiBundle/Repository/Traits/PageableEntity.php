<?php

namespace SnapRapid\ApiBundle\Repository\Traits;

use SnapRapid\ApiBundle\Adapter\DoctrineODMMongoDBAdapterNoKeys;

trait PageableEntity
{
    use PageableObject;

    /**
     * @param array $filters
     * @param array $sorting
     * 
     * @return DoctrineORMAdapter
     */
    protected function getPagerAdapter(array $filters = [], array $sorting = [])
    {
        return new DoctrineODMMongoDBAdapterNoKeys(
            $this->getResultsQueryBuilder($filters, $sorting),
            $this->getPrimeFields()
        );
    }

    /**
     * @param array $filters
     * @param array $sorting
     *
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    abstract protected function getResultsQueryBuilder(array $filters = [], array $sorting = []);

    /**
     * @return array
     */
    protected function getPrimeFields()
    {
        return [];
    }
}
