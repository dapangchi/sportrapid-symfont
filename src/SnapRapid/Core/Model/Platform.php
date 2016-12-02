<?php

namespace SnapRapid\Core\Model;

use SnapRapid\Core\Model\Base\PersistentModel;

class Platform extends PersistentModel
{
    const COVERAGE_TYPE_SOCIAL  = 'social';
    const COVERAGE_TYPE_DIGITAL = 'digital';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $displayName;

    /**
     * @var string
     */
    protected $iconClass;

    /**
     * @var bool
     */
    protected $hasImages;

    /**
     * @var bool
     */
    protected $hasVideos;

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
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @return string
     */
    public function getIconClass()
    {
        return $this->iconClass;
    }

    /**
     * @return bool
     */
    public function getHasImages()
    {
        return $this->hasImages;
    }

    /**
     * @return bool
     */
    public function getHasVideos()
    {
        return $this->hasVideos;
    }
}
