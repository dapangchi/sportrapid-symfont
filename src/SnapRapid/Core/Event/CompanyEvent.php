<?php

namespace SnapRapid\Core\Event;

use SnapRapid\Core\Model\Company;
use Symfony\Component\EventDispatcher\Event;

class CompanyEvent extends Event
{
    /**
     * @var Company
     */
    protected $company;

    /**
     * @param Company $company
     */
    public function __construct(Company $company = null)
    {
        $this->company = $company;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }
}
