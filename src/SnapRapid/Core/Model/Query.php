<?php

namespace SnapRapid\Core\Model;

use Gedmo\Timestampable\Traits\Timestampable;
use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Model\Collection\ArrayCollection;
use SnapRapid\Core\Model\Collection\Collection;

class Query extends PersistentModel
{
    use Timestampable;

    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var Collection|Topic[]
     */
    protected $topics;

    public function __construct()
    {
        $this->topics  = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return Collection|Topic[]
     */
    public function getTopics()
    {
        return $this->topics;
    }
}
