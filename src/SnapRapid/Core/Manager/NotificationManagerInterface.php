<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Model\User;

interface NotificationManagerInterface
{
    public function createCompanyMemberInviteNotification(CompanyMember $companyMember, User $user);
}
