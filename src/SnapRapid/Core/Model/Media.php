<?php

namespace SnapRapid\Core\Model;

use SnapRapid\Core\Model\Base\PersistentModel;

class Media extends PersistentModel
{
    /**
     * @var int
     */
    protected $type;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var Collection|Label[]
     */
    protected $labels;

    /**
     * @var Collection|Topic[]
     */
    protected $topics;

    /**
     * @var Collection|Post[]
     */
    protected $posts;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return Collection|Label[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @return Collection|Topic[]
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * @return Collection|Post[]
     */
    public function getPosts()
    {
        return $this->posts;
    }
}
