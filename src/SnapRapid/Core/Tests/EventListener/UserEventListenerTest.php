<?php

namespace SnapRapid\Core\Tests\EventListener;

use SnapRapid\Core\Event\UserEvent;
use SnapRapid\Core\EventListener\UserEventListener;
use SnapRapid\Core\Model\User;

class UserEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailer;

    /**
     * @var UserEventListener
     */
    private $userEventListener;

    /**
     * Set up the listener with mocked services
     */
    public function setUp()
    {
        $this->mailer            = $this->getMock('SnapRapid\Core\Mailer\UserMailerInterface');
        $this->userEventListener = new UserEventListener($this->mailer);
    }

    public function testOnCreate()
    {
        $this->mailer
            ->expects($this->once())
            ->method('sendAccountActivationEmail')
            ->with(
                $this->isInstanceOf('SnapRapid\Core\Model\User')
            );

        $userEvent = new UserEvent(
            new User()
        );

        $this->userEventListener->onCreate($userEvent);
    }

    public function testOnActivationResendRequest()
    {
        $this->mailer
            ->expects($this->once())
            ->method('sendAccountActivationEmail')
            ->with(
                $this->isInstanceOf('SnapRapid\Core\Model\User')
            );

        $userEvent = new UserEvent(
            new User()
        );

        $this->userEventListener->onActivationResendRequested($userEvent);
    }

    public function testOnActivate()
    {
        $this->mailer
            ->expects($this->once())
            ->method('sendAccountCreatedEmail')
            ->with(
                $this->isInstanceOf('SnapRapid\Core\Model\User')
            );

        $userEvent = new UserEvent(
            new User()
        );

        $this->userEventListener->onActivate($userEvent);
    }

    public function testOnUpdate()
    {
    }

    public function testOnRemove()
    {
        $this->mailer
            ->expects($this->once())
            ->method('sendAccountRemovedEmail')
            ->with(
                $this->isInstanceOf('SnapRapid\Core\Model\User')
            );

        $userEvent = new UserEvent(
            new User()
        );

        $this->userEventListener->onRemove($userEvent);
    }

    public function testOnLogIn()
    {
    }

    public function testOnPasswordResetRequested()
    {
        $this->mailer
            ->expects($this->once())
            ->method('sendPasswordResetRequestEmail')
            ->with(
                $this->isInstanceOf('SnapRapid\Core\Model\User')
            );

        $userEvent = new UserEvent(
            new User()
        );

        $this->userEventListener->onPasswordResetRequested($userEvent);
    }

    public function testOnPasswordReset()
    {
        $this->mailer
            ->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with(
                $this->isInstanceOf('SnapRapid\Core\Model\User')
            );

        $userEvent = new UserEvent(
            new User()
        );

        $this->userEventListener->onPasswordReset($userEvent);
    }

    public function testOnProfilePageView()
    {
    }
}
