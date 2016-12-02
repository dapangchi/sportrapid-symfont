<?php

namespace SnapRapid\Core\Model;

use Gedmo\SoftDeleteable\Traits\SoftDeleteable;
use Gedmo\Timestampable\Traits\Timestampable;
use SnapRapid\ApiBundle\Security\User\SecurityUser;
use SnapRapid\Core\Model\Base\PersistentModel;

class Notification extends PersistentModel
{
    const POSITIVE_RESPONSE = 1;
    const NEGATIVE_RESPONSE = 0;

    use Timestampable,
        SoftDeleteable;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $positiveResponseBtn;

    /**
     * @var string
     */
    protected $negativeResponseBtn;

    /**
     * @var string
     */
    protected $neutralResponseBtn;

    /**
     * @var string
     */
    protected $responseEvent;

    /**
     * @var int
     */
    protected $relatedObjectId;

    /**
     * @var int
     */
    protected $response;

    /**
     * Check if a given security user is the same as the user this notification is intended for
     *
     * @param SecurityUser $securityUser
     *
     * @return bool
     */
    public function isIntendedFor(SecurityUser $securityUser)
    {
        return $securityUser->getId() == $this->getUser()->getId();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return Notification
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return Notification
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getPositiveResponseBtn()
    {
        return $this->positiveResponseBtn;
    }

    /**
     * @param string $positiveResponseBtn
     *
     * @return Notification
     */
    public function setPositiveResponseBtn($positiveResponseBtn)
    {
        $this->positiveResponseBtn = $positiveResponseBtn;

        return $this;
    }

    /**
     * @return string
     */
    public function getNegativeResponseBtn()
    {
        return $this->negativeResponseBtn;
    }

    /**
     * @param string $negativeResponseBtn
     *
     * @return Notification
     */
    public function setNegativeResponseBtn($negativeResponseBtn)
    {
        $this->negativeResponseBtn = $negativeResponseBtn;

        return $this;
    }

    /**
     * @return string
     */
    public function getNeutralResponseBtn()
    {
        return $this->neutralResponseBtn;
    }

    /**
     * @param string $neutralResponseBtn
     *
     * @return Notification
     */
    public function setNeutralResponseBtn($neutralResponseBtn)
    {
        $this->neutralResponseBtn = $neutralResponseBtn;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponseEvent()
    {
        return $this->responseEvent;
    }

    /**
     * @param string $responseEvent
     *
     * @return Notification
     */
    public function setResponseEvent($responseEvent)
    {
        $this->responseEvent = $responseEvent;

        return $this;
    }

    /**
     * @return int
     */
    public function getRelatedObjectId()
    {
        return $this->relatedObjectId;
    }

    /**
     * @param int $relatedObjectId
     *
     * @return Notification
     */
    public function setRelatedObjectId($relatedObjectId)
    {
        $this->relatedObjectId = $relatedObjectId;

        return $this;
    }

    /**
     * @return int
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param int $response
     *
     * @return Notification
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }
}
