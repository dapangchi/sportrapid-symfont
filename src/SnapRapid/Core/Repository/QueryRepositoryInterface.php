<?php

namespace SnapRapid\Core\Repository;

use SnapRapid\Core\Repository\Base\PageableModelInterface;
use SnapRapid\Core\Repository\Base\PersistentModelRepositoryInterface;

interface QueryRepositoryInterface extends
    PersistentModelRepositoryInterface,
    PageableModelInterface
{
}
