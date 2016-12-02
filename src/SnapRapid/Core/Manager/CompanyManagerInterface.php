<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Model\Company;
use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Model\User;

interface CompanyManagerInterface
{
    public function createNewCompany(User $user);
    public function saveNewCompany(Company $company, User $user);
    public function updateCompany(Company $company, Company $oldCompany, User $user);
    public function removeCompany(Company $company, User $user);
    public function decorateCompany(Company $company);
    public function saveNewCompanyMember(CompanyMember $companyMember);
    public function updateCompanyMember(CompanyMember $companyMember);
    public function removeCompanyMember(CompanyMember $companyMember);
    public function acceptCompanyMemberInvite($companyMemberId, User $user);
}
