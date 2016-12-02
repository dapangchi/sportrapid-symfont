<?php

namespace SnapRapid\Core\Manager;

use SnapRapid\Core\Model\User;

interface UserManagerInterface
{
    public function createNewUser(User $createdBy = null);
    public function saveNewUser(User $user, User $activeUser = null);
    public function updateCanonicalFields(User $user);
    public function updateUser(User $user);
    public function generatePasswordResetToken(User $user);
    public function resetPassword(User $user, $newPassword);
    public function setAccountActivationToken(User $user);
    public function resendAccountActivation(User $user);
    public function activateAccount(User $user, $password);
    public function removeUser(User $user);
    public function findUserByEmail($email);
    public function findUserById($id);
    public function findUserByResetPasswordToken($token);
    public function findUserByAccountActivationToken($token);
    public function decorateUser(User $user, $isSelf = false);
}
