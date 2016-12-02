<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AccountActivationTokenValidator extends ConstraintValidator
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
        // we use the token to find the user in the first place, so if the user is not here at this point
        // then we deduce that the token is invalid
        if (!$user || !$user->getId()) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('accountActivationToken')
                ->addViolation();
        }
    }
}
