<?php

namespace SnapRapid\Core\Mailer;

use SnapRapid\Core\Model\User;

interface UserMailerInterface
{
    public function sendAccountCreatedEmail(User $user);
    public function sendAccountActivationEmail(User $getUser);
    public function sendAccountRemovedEmail(User $user);
    public function sendPasswordResetRequestEmail(User $user);
    public function sendPasswordResetEmail(User $user);
}
