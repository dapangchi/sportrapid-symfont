<?php

namespace SnapRapid\Core\Repository;

use SnapRapid\Core\Repository\Base\PageableModelInterface;
use SnapRapid\Core\Repository\Base\PersistentModelRepositoryInterface;

interface PlatformRepositoryInterface extends
    PersistentModelRepositoryInterface,
    PageableModelInterface
{
}
