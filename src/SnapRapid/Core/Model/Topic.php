<?php

namespace SnapRapid\Core\Model;

use Gedmo\Timestampable\Traits\Timestampable;
use SnapRapid\Core\Model\Base\PersistentModel;

class Topic extends PersistentModel
{
    use Timestampable;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $isRootTopic;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getIsRootTopic()
    {
        return $this->isRootTopic;
    }
}
