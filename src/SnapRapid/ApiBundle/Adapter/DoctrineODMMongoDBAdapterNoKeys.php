<?php

namespace SnapRapid\ApiBundle\Adapter;

use Doctrine\ODM\MongoDB\Query\Builder;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;

/**
 * Class DoctrineODMMongoDBAdapterNoKeys
 *
 * This is an override file for the extended class.
 * Priming of references is facilitated, needs to be turned off for the number of results and on for the `getSlice`
 * The method `getSlice` is changed to disable the use of identifier keys.
 * The remaining bits of this file were only included due to the private `$queryBuilder` property.
 */
class DoctrineODMMongoDBAdapterNoKeys extends DoctrineODMMongoDBAdapter
{
    private $queryBuilder;

    /**
     * @var array
     */
    private $primeFields;

    /**
     * Constructor.
     *
     * @param Builder $queryBuilder A DoctrineMongo query builder.
     * @param array   $primeFields
     */
    public function __construct(Builder $queryBuilder, array $primeFields)
    {
        $this->queryBuilder = $queryBuilder;
        $this->primeFields  = $primeFields;
    }

    /**
     * Returns the query builder.
     *
     * @return Builder The query builder.
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        // un-prime the prime-fields as this causes wrong num results otherwise
        foreach ($this->primeFields as $primeField) {
            $this->queryBuilder->field($primeField)->prime(false);
        }

        return $this->queryBuilder->getQuery()->count();
    }

    /**
     * Over-ride of this method to set the use identifier keys to false
     * This prevents binary ids collapsing the data sets as the string representations of these are "<Mongo Binary Id>"
     *
     * Added prime fields functionality at this point. If it is added at the point of creating the query builder
     * then the number of pages and results is incorrect.
     *
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        foreach ($this->primeFields as $primeField) {
            $this->queryBuilder->field($primeField)->prime();
        }

        return $this->queryBuilder
            ->limit($length)
            ->skip($offset)
            ->getQuery()
            ->execute()
            ->setUseIdentifierKeys(false); # this line added
    }
}
