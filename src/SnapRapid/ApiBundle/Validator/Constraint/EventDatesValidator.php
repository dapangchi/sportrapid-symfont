<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use SnapRapid\Core\Model\Event;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EventDatesValidator extends ConstraintValidator
{
    /**
     * @param Event      $event
     * @param Constraint $constraint
     */
    public function validate($event, Constraint $constraint)
    {
        // check if a date range is required
        if ($event->getDateRangeType() != Event::DATE_RANGE_TYPE_CUSTOM) {
            return;
        }

        // check that both dates are present
        if (!$event->getDateRangeStart() || !$event->getDateRangeEnd()) {
            // check if start date is present
            if (!$event->getDateRangeStart()) {
                $this->context
                    ->buildViolation($constraint->missingRequiredDateRangeStart)
                    ->atPath('dateRangeStart')
                    ->addViolation();
            }

            // check if end date is present
            if (!$event->getDateRangeEnd()) {
                $this->context
                    ->buildViolation($constraint->missingRequiredDateRangeStart)
                    ->atPath('dateRangeEnd')
                    ->addViolation();
            }

            return;
        }

        // check that the dates are the correct way around
        if ($event->getDateRangeStart() > $event->getDateRangeEnd()) {
            $this->context
                ->buildViolation($constraint->datesInWrongOrder)
                ->atPath('dateRangeEnd')
                ->addViolation();

            return;
        }

        // check ancestors to check that we are not widening the date range
        $parent = $event->getParent();
        while ($parent) {
            if ($parent->getDateRangeType() == Event::DATE_RANGE_TYPE_CUSTOM) {
                $startDateTooEarly = $event->getDateRangeStart() < $parent->getDateRangeStart();
                $endDateTooLate    = $event->getDateRangeEnd() > $parent->getDateRangeEnd();
                if ($startDateTooEarly || $endDateTooLate) {
                    if ($startDateTooEarly) {
                        $this->context
                            ->buildViolation(
                                sprintf($constraint->startDateTooEarly, $parent->getDateRangeStart()->format('d/m/Y'))
                            )
                            ->atPath('dateRangeStart')
                            ->addViolation();
                    }
                    if ($endDateTooLate) {
                        $this->context
                            ->buildViolation(
                                sprintf($constraint->endDateTooLate, $parent->getDateRangeEnd()->format('d/m/Y'))
                            )
                            ->atPath('dateRangeEnd')
                            ->addViolation();
                    }

                    return;
                }
            }

            $parent = $parent->getParent();
        }
    }
}
