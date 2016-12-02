<?php

namespace SnapRapid\ApiBundle\Repository\Traits;

use Pagerfanta\Pagerfanta;

trait PageableObject
{
    /**
     * @param array $filter
     * @param array $sorting
     * @param int   $count
     * @param int   $page
     *
     * @return Pagerfanta
     */
    public function getResultsPager(array $filter, array $sorting, $count, $page)
    {
        $adapter = $this->getPagerAdapter($filter, $sorting);

        $pager = new Pagerfanta($adapter);
        $pager->setMaxPerPage($count);
        $pager->setCurrentPage($page);

        return $pager;
    }

    /**
     * @return \Pagerfanta\Adapter\AdapterInterface
     */
    abstract protected function getPagerAdapter();
}
