<?php

namespace SnapRapid\Core\Model;

use Gedmo\Blameable\Traits\Blameable;
use Gedmo\Timestampable\Traits\Timestampable;
use Gedmo\Tree\Traits\MaterializedPath;
use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Model\Collection\ArrayCollection;
use SnapRapid\Core\Model\Collection\Collection;

class Event extends PersistentModel
{
    const DATE_RANGE_TYPE_CUSTOM       = 1;
    const DATE_RANGE_TYPE_TODAY        = 2;
    const DATE_RANGE_TYPE_THIS_WEEK    = 3;
    const DATE_RANGE_TYPE_LAST_7_DAYS  = 4;
    const DATE_RANGE_TYPE_THIS_MONTH   = 5;
    const DATE_RANGE_TYPE_LAST_30_DAYS = 6;

    use Timestampable,
        Blameable,
        MaterializedPath;

    /**
     * Tree locking
     * 
     * @var \DateTime|null
     */
    protected $lockTime;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $dateRangeType = self::DATE_RANGE_TYPE_CUSTOM;

    /**
     * @var \DateTime|null
     */
    protected $dateRangeStart = null;

    /**
     * @var \DateTime|null
     */
    protected $dateRangeEnd = null;

    /**
     * @var Collection|Topic[]
     */
    protected $topics;

    public function __construct()
    {
        $this->topics  = new ArrayCollection();
    }

    /**
     * @return \DateTime|null
     */
    public function getLockTime()
    {
        return $this->lockTime;
    }

    /**
     * @param \DateTime|null $lockTime
     *
     * @return Event
     */
    public function setLockTime($lockTime)
    {
        $this->lockTime = $lockTime;

        return $this;
    }

    /**
     * @return Collection|Topic[]
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * @param Topic $topic
     *
     * @return Event
     */
    public function addTopic($topic)
    {
        $this->topics->add($topic);

        return $this;
    }

    /**
     * @param Topic $topic
     *
     * @return Event
     */
    public function removeTopic($topic)
    {
        $this->topics->removeElement($topic);

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Event
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getDateRangeType()
    {
        return $this->dateRangeType;
    }

    /**
     * @param int $dateRangeType
     *
     * @return Event
     */
    public function setDateRangeType($dateRangeType)
    {
        $this->dateRangeType = $dateRangeType;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateRangeStart()
    {
        return $this->dateRangeStart;
    }

    /**
     * @param \DateTime|null $dateRangeStart
     *
     * @return Event
     */
    public function setDateRangeStart($dateRangeStart)
    {
        $this->dateRangeStart = $dateRangeStart;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateRangeEnd()
    {
        return $this->dateRangeEnd;
    }

    /**
     * @param \DateTime|null $dateRangeEnd
     *
     * @return Event
     */
    public function setDateRangeEnd($dateRangeEnd)
    {
        $this->dateRangeEnd = $dateRangeEnd;

        return $this;
    }
}
