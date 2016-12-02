<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use SnapRapid\Core\Model\Event;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EventParentValidator extends ConstraintValidator
{
    /**
     * @param Event      $event
     * @param Constraint $constraint
     */
    public function validate($event, Constraint $constraint)
    {
        if ($event->getParent()) {
            // check self first
            if ($event->getParent()->getId() == $event->getId()) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath('parent')
                    ->addViolation();

                return;
            }

            // check that we are not trying to set the parent event to a child of this event
            if (count($event->getChildren())) {
                $checkChildren = function ($parent) use (&$checkChildren, $event, $constraint) {
                    foreach ($parent->getChildren() as $child) {
                        if ($child->getId() == $event->getParent()->getId()) {
                            $this->context
                                ->buildViolation($constraint->message)
                                ->atPath('parent')
                                ->addViolation();

                            return false;
                        }

                        $isValid = $checkChildren($child);
                        if (!$isValid) {
                            return false;
                        }
                    }

                    return true;
                };

                $checkChildren($event);
            }
        }
    }
}
