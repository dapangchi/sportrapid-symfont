<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class InvitationToken extends Constraint
{
    public $message = 'This invitation link is not valid. Please contact your company administrator to request a new one.';

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
        return 'invitation_token_validator';
    }
}
