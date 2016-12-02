<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Util\Canonicalizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CompanyMemberLimitValidator extends ConstraintValidator
{
    /**
     * @var Canonicalizer
     */
    private $canonicalizer;

    /**
     * @param Canonicalizer $canonicalizer
     */
    public function __construct(Canonicalizer $canonicalizer)
    {
        $this->canonicalizer = $canonicalizer;
    }

    /**
     * @param CompanyMember $companyMember
     * @param Constraint    $constraint
     */
    public function validate($companyMember, Constraint $constraint)
    {
        $company = $companyMember->getCompany();
        if ($company->getMembers()->count() > $company->getMaxMembers()) {
            $this->context
                ->buildViolation(sprintf($constraint->message, $company->getMaxMembers()))
                ->atPath('email')
                ->addViolation();
        }
    }
}
