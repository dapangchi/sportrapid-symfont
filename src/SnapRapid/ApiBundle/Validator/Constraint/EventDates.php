<?php

namespace SnapRapid\ApiBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EventDates extends Constraint
{
    public $missingRequiredDateRangeStart = 'You must enter a start date for the custom date range.';
    public $missingRequiredDateRangeEnd   = 'You must enter an end date for the custom date range.';
    public $datesInWrongOrder             = 'The end date must come after the start date.';
    public $startDateTooEarly             = 'The start date cannot be earlier than the start date of it\'s parent event (%s)';
    public $endDateTooLate                = 'The end date cannot be later than the end date of it\'s parent event (%s)';

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
        return 'event_dates_validator';
    }
}
