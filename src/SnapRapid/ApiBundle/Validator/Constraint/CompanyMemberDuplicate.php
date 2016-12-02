<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CompanyMemberDuplicate extends Constraint
{
    public $message = 'This email address is already in use by another member of this company.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'company_member_duplicate_validator';
    }
}
