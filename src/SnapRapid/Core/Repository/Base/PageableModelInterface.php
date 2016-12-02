<?php

namespace SnapRapid\Core\Repository\Base;

interface PageableModelInterface
{
    /**
     * @param array $filters
     * @param array $sorting
     * @param int   $count
     * @param int   $page
     *
     * @return Pagerfanta
     */
    public function getResultsPager(array $filters, array $sorting, $count, $page);
}
