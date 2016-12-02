<?php

namespace SnapRapid\Core\Model;

use SnapRapid\Core\Model\Base\PersistentModel;
use SnapRapid\Core\Model\Collection\Collection;

class Post extends PersistentModel
{
    const POST_TYPE_TEXT_ONLY = 0;
    const POST_TYPE_IMAGE     = 1;
    const POST_TYPE_VIDEO     = 2;
    const POST_TYPE_URL       = 3;
    const POST_TYPE_BOTH      = 4;

    /**
     * @var \MongoBinData
     */
    protected $id;

    /**
     * @var array
     */
    protected $statistics;

    /**
     * @var array
     */
    protected $tags;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var \DateTime
     */
    protected $publishedAt;

    /**
     * @var int
     */
    protected $postType;

    /**
     * @var array
     */
    protected $obtainedBy;

    /**
     * @var array
     */
    protected $images;

    /**
     * @var array
     */
    protected $videos;

    /**
     * @var array
     */
    protected $medias;

    /**
     * @var array
     */
    protected $valuation;

    /**
     * @var float
     */
    protected $sentiment;

    /**
     * @var array
     */
    protected $webContent;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var bool
     */
    protected $verified;

    /**
     * @var Platform
     */
    protected $platform;

    /**
     * @var Author
     */
    protected $author;

    /**
     * @var Collection|Topic[]
     */
    protected $topics;

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
     * @return array
     */
    public function getStatistics()
    {
        return $this->statistics;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @return int
     */
    public function getPostType()
    {
        return $this->postType;
    }

    /**
     * @return array
     */
    public function getObtainedBy()
    {
        return $this->obtainedBy;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @return array
     */
    public function getVideos()
    {
        return $this->videos;
    }

    /**
     * @return array
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * @return array
     */
    public function getValuation()
    {
        return $this->valuation;
    }

    /**
     * @return float
     */
    public function getSentiment()
    {
        return $this->sentiment;
    }

    /**
     * @param $sentiment
     *
     * @return Post
     */
    public function setSentiment($sentiment)
    {
        $this->sentiment = $sentiment;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getVerifiedSentiment()
    {
        if (!is_numeric($this->sentiment)) {
            return;
        }

        if ($this->sentiment >= -1 && $this->sentiment < -0.33) {
            return -1;
        }

        if ($this->sentiment >= 0.33 && $this->sentiment <= 0.33) {
            return 0;
        }

        if ($this->sentiment > 0.33 && $this->sentiment <= 1) {
            return 1;
        }
    }

    /**
     * @return array
     */
    public function getWebContent()
    {
        return $this->webContent;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @return Platform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return Author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return Collection|Topic[]
     */
    public function getTopics()
    {
        return $this->topics;
    }
}
