<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use SnapRapid\Core\Repository\CompanyRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InvitationTokenValidator extends ConstraintValidator
{
    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * InvitationTokenValidator constructor.
     *
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(CompanyRepositoryInterface $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * @param mixed      $user
     * @param Constraint $constraint
     */
    public function validate($user, Constraint $constraint)
    {
        $companyMember = $this->companyRepository->findMemberByInvitationToken($user->getInvitationToken());

        if (!$companyMember) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('invitationToken')
                ->addViolation();
        }
    }
}
