<?php

namespace SnapRapid\Core\Repository;

use SnapRapid\Core\Model\User;
use SnapRapid\Core\Repository\Base\PageableModelInterface;
use SnapRapid\Core\Repository\Base\PersistentModelRepositoryInterface;

interface UserRepositoryInterface extends
    PersistentModelRepositoryInterface,
    PageableModelInterface
{
    public function updatePassword(User $user);
    public function getAllEnabled();
    public function detachBlameableSubscriber();
}
