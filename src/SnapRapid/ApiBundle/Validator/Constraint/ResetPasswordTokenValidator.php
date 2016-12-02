<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ResetPasswordTokenValidator extends ConstraintValidator
{
    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @param mixed      $user
     * @param Constraint $constraint
     */
    public function validate($user, Constraint $constraint)
    {
        if (!$user || !$user->getId() || $user->getPasswordResetTokenExpiresAt() < new \DateTime()) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('passwordResetToken')
                ->addViolation();
        }
    }
}
