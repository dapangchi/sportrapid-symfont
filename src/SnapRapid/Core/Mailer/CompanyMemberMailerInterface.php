<?php

namespace SnapRapid\Core\Mailer;

use SnapRapid\Core\Model\CompanyMember;

interface CompanyMemberMailerInterface
{
    public function sendMemberAddedEmail(CompanyMember $companyMember);
    public function sendInviteAcceptedEmails(CompanyMember $companyMember);
    public function sendInviteDeclinedEmails(CompanyMember $companyMember);
    public function sendMemberRemovedEmails(CompanyMember $companyMember);
}
