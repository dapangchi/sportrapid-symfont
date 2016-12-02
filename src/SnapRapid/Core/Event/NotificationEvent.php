<?php

namespace SnapRapid\Core\Event;

use SnapRapid\Core\Model\Notification;
use Symfony\Component\EventDispatcher\Event;

class NotificationEvent extends Event
{
    const STATUS_SUCCESS = 0;
    const STATUS_ERROR   = 1;

    /**
     * @var Notification
     */
    protected $notification;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var string
     */
    protected $message;

    /**
     * @param Notification $notification
     */
    public function __construct(Notification $notification = null)
    {
        $this->notification = $notification;
    }

    /**
     * @return Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return NotificationEvent
     */
    public function setStatus($status)
    {
        $this->status = $status;

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
     * @return NotificationEvent
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }
}
