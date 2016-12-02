<?php

namespace SnapRapid\Core\Model;

use SnapRapid\Core\Model\Base\PersistentModel;

class Label extends PersistentModel
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $classifier;

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
    public function getClassifier()
    {
        return $this->classifier;
    }
}
