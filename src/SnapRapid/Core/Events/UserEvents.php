<?php

namespace SnapRapid\Core\Events;

final class UserEvents
{
    // Account events
    const USER_ACCOUNT_CREATED                     = 'user.account.created';
    const USER_ACCOUNT_ACTIVATED                   = 'user.account.activated';
    const USER_ACCOUNT_ACTIVATION_RESEND_REQUESTED = 'user.account.activationresend';
    const USER_ACCOUNT_UPDATED                     = 'user.account.updated';
    const USER_ACCOUNT_REMOVED                     = 'user.account.removed';

    // Log in
    const LOGGED_IN = 'user.logged_in';

    // Password resetting
    const USER_PASSWORD_RESET_REQUESTED = 'user.passwordreset.requested';
    const USER_PASSWORD_RESET           = 'user.passwordreset.reset';
}
