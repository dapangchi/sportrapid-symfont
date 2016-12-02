<?php

namespace SnapRapid\Core\Event;

use SnapRapid\Core\Model\CompanyMember;

class CompanyMemberEvent extends CompanyEvent
{
    /**
     * @var CompanyMember
     */
    private $companyMember;

    /**
     * @param CompanyMember $companyMember
     */
    public function __construct(CompanyMember $companyMember)
    {
        $this->companyMember = $companyMember;

        parent::__construct($companyMember->getCompany());
    }

    /**
     * @return CompanyMember
     */
    public function getCompanyMember()
    {
        return $this->companyMember;
    }
}
