<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Util\Canonicalizer;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CompanyMemberDuplicateValidator extends ConstraintValidator
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
        if ($companyMember->getUser()) {
            $newEmail = $companyMember->getUser()->getEmail();
        } else {
            $newEmail = $companyMember->getEmail();
        }
        $newEmail = $this->canonicalizer->canonicalize($newEmail);

        // check this against the other members of the company
        foreach ($companyMember->getCompany()->getMembers() as $existingCompanyMember) {
            if ($existingCompanyMember->getId() == $companyMember->getId()) {
                continue;
            }

            if ($existingCompanyMember->getUser()) {
                $existingEmail = $existingCompanyMember->getUser()->getEmail();
            } else {
                $existingEmail = $existingCompanyMember->getEmail();
            }
            if ($existingEmail) {
                $existingEmail = $this->canonicalizer->canonicalize($existingEmail);

                if ($newEmail == $existingEmail) {
                    $this->context
                        ->buildViolation($constraint->message)
                        ->atPath('email')
                        ->addViolation();

                    return;
                }
            }
        }
    }
}
