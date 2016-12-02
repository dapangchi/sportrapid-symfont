<?php

namespace SnapRapid\Core\Repository;

use SnapRapid\Core\Model\User;
use SnapRapid\Core\Repository\Base\PageableModelInterface;
use SnapRapid\Core\Repository\Base\PersistentModelRepositoryInterface;

interface CompanyRepositoryInterface extends
    PersistentModelRepositoryInterface,
    PageableModelInterface
{
    public function getAll();
    public function getMemberOfCompanyIds(User $user);
    public function getCompanyIdsByName($searchString);
    public function getCompanyMemberById($companyMemberId);
    public function getCompanyMembersByEmail($email);
    public function findMemberByInvitationToken($invitationToken);
}
