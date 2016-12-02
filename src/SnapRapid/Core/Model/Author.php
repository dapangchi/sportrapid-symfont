<?php

namespace SnapRapid\Core\Model;

use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Model\Collection\Collection;

class Author extends PersistentModel
{
    /**
     * @var \MongoBinData
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $screenName;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var bool
     */
    protected $verified;

    /**
     * @var array
     */
    protected $statistics;

    /**
     * @var Platform
     */
    protected $platform;

    /**
     * @var Collection|Post[]
     */
    protected $posts;

    /**
     * @return string
     */
    public function getIdAsString()
    {
        return bin2hex($this->id);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getScreenName()
    {
        return $this->screenName;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @return array
     */
    public function getStatistics()
    {
        return $this->statistics;
    }

    /**
     * @return Platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return Collection|Post[]
     */
    public function getPosts()
    {
        return $this->posts;
    }
}
