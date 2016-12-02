<?php

namespace SnapRapid\Core\Events;

final class CompanyEvents
{
    // Account events
    const COMPANY_CREATED = 'company.created';
    const COMPANY_UPDATED = 'company.updated';
    const COMPANY_REMOVED = 'company.removed';

    // Member events
    const MEMBER_ADDED                   = 'company.member.added';
    const MEMBER_UPDATED                 = 'company.member.updated';
    const MEMBER_INVITE_RESPONDED_TO     = 'company.member.invite_responded_to';
    const MEMBER_INVITE_ACCEPTED         = 'company.member.invite_accepted';
    const MEMBER_INVITE_DECLINED         = 'company.member.invite_declined';
    const MEMBER_INVITE_RESEND_REQUESTED = 'company.member.invite_resend';
    const MEMBER_REMOVED                 = 'company.member.removed';
}
