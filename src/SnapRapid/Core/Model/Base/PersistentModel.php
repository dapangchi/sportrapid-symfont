<?php

namespace SnapRapid\Core\Model\Base;

class PersistentModel
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @return bool
     */
    public function isNew()
    {
        return (bool) !$this->id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
