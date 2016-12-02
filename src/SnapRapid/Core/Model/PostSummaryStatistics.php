<?php

namespace SnapRapid\Core\Model;

use SnapRapid\Core\Model\Base\PersistentModel;

class PostSummaryStatistics extends PersistentModel
{
    /**
     * @var \MongoBinData
     */
    protected $id;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var Topic
     */
    protected $topic;

    /**
     * @var Platform
     */
    protected $platform;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @return string
     */
    public function getId()
    {
        return bin2hex($this->id);
    }

    public function setId($id)
    {
        $this->id = bin2hex($id);
    }

    /**
     * @return string
     */
    public function getIdAsString()
    {
        return bin2hex($this->id);
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return Topic
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return Platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
