<?php

namespace SnapRapid\Core\Tests\Manager;

use SnapRapid\Core\Events\UserEvents;
use SnapRapid\Core\Manager\UserManager;
use SnapRapid\Core\Model\User;
use SnapRapid\Core\Util\Canonicalizer;

class UserManagerTest extends ManagerTestHelper
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $companyRepository;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * Set up the manager with mocked repo and event dispatcher
     */
    public function setUp()
    {
        parent::setUp();

        $this->repository        = $this->getMock('SnapRapid\Core\Repository\UserRepositoryInterface');
        $this->companyRepository = $this->getMock('SnapRapid\Core\Repository\CompanyRepositoryInterface');
        $this->userManager       = new UserManager(
            $this->repository,
            $this->companyRepository,
            $this->dispatcher,
            new Canonicalizer()
        );

        $this->userManager->setNotificationManager($this->notificationManager);
    }

    /**
     * Create a new user object
     */
    public function testCreateNewUser()
    {
        $user  = $this->userManager->createNewUser();
        $roles = $user->getRoles();

        $this->assertInstanceOf('SnapRapid\Core\Model\User', $user);
        $this->assertCount(1, $roles);
        $this->assertEquals(User::ROLE_USER, $roles[0]);
    }

    /**
     * Save a new user with no member invites
     */
    public function testSaveNewUser()
    {
        $this->expectRepositoryToCallMethod('save');
        $this->expectDispatcherToDispatchEvent(UserEvents::USER_ACCOUNT_CREATED, 'SnapRapid\Core\Event\UserEvent');

        $user = $this->userManager->createNewUser();
        $this->userManager->saveNewUser($user);
    }

    /**
     * Generate a password reset token
     */
    public function testGeneratePasswordResetToken()
    {
        $this->expectRepositoryToCallMethod('detachBlameableSubscriber');
        $this->expectRepositoryToCallMethod('save');
        $this->expectDispatcherToDispatchEvent(
            UserEvents::USER_PASSWORD_RESET_REQUESTED,
            'SnapRapid\Core\Event\UserEvent'
        );

        $user = $this->userManager->createNewUser();
        $this->userManager->generatePasswordResetToken($user);

        $this->assertNotNull($user->getPasswordResetToken());
        $this->assertLessThanOrEqual(new \DateTime('+1 day'), $user->getPasswordResetTokenExpiresAt());
    }

    /**
     * Try to generate the token again
     */
    public function testGeneratePasswordResetTokenTwice()
    {
        $user = $this->userManager->createNewUser();
        $this->userManager->generatePasswordResetToken($user);
        $token = $user->getPasswordResetToken();
        $this->userManager->generatePasswordResetToken($user);
        $this->assertEquals($token, $user->getPasswordResetToken());
    }

    /**
     * Use the password reset token to reset the user's password
     */
    public function testResetPassword()
    {
        $user = $this->userManager->createNewUser();
        $user->setPasswordResetToken('sometoken');

        $this->expectRepositoryToCallMethod('detachBlameableSubscriber');
        $this->expectRepositoryToCallMethod('save');
        $this->expectDispatcherToDispatchEvent(UserEvents::USER_PASSWORD_RESET, 'SnapRapid\Core\Event\UserEvent');

        $this->userManager->resetPassword($user, 'newpassword');
        $this->assertNull($user->getPasswordResetToken());
    }

    /**
     * Remove the user
     */
    public function testRemoveUser()
    {
        $this->expectRepositoryToCallMethod('remove');
        $this->expectDispatcherToDispatchEvent(UserEvents::USER_ACCOUNT_REMOVED, 'SnapRapid\Core\Event\UserEvent');

        $user = $this->userManager->createNewUser();
        $this->userManager->removeUser($user);
    }
}
