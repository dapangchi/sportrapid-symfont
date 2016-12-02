<?php

namespace SnapRapid\ApiBundle\Mailer;

use SnapRapid\Core\Mailer\CompanyMemberMailerInterface;
use SnapRapid\Core\Model\CompanyMember;
use SnapRapid\Core\Model\User;

class CompanyMemberMailer extends BaseMailer implements CompanyMemberMailerInterface
{
    /**
     * Send email to a newly added member
     *
     * @param CompanyMember $companyMember
     */
    public function sendMemberAddedEmail(CompanyMember $companyMember)
    {
        $context = [
            'memberAdded' => $companyMember,
            'company'     => $companyMember->getCompany(),
        ];

        if ($companyMember->getMatchingUser()) {
            // if the member who was added is a user send them an email to get them to confirm
            $this->sendMessage(
                'SnapRapidApiBundle:Emails:Company/member_added_confirm.eml.twig',
                $context,
                $companyMember->getMatchingUser()
            );
        } elseif ($companyMember->getEmail()) {
            // send an invite email to the non-user
            $memberNonUser = new User();
            $memberNonUser->setEmail($companyMember->getEmail());
            $this->sendMessage(
                'SnapRapidApiBundle:Emails:Company/member_added_invite.eml.twig',
                $context,
                $memberNonUser
            );
        }
    }

    /**
     * Send confirmation and notification email after a member confirms
     *
     * @param CompanyMember $companyMember
     */
    public function sendInviteAcceptedEmails(CompanyMember $companyMember)
    {
        $context = [
            'memberWhoAccepted' => $companyMember,
            'company'           => $companyMember->getCompany(),
        ];

        // confirmation email to the user
        $this->sendMessage(
            'SnapRapidApiBundle:Emails:Company/member_invite_accepted_confirmation.eml.twig',
            $context,
            $companyMember->getUser()
        );

        // notification emails to other company members
        foreach ($companyMember->getCompany()->getCompanyAdminUserMembers() as $member) {
            if ($member->getUser()->getId() != $companyMember->getUser()->getId()) {
                $context['user'] = $member->getUser();
                $this->sendMessage(
                    'SnapRapidApiBundle:Emails:Company/member_invite_accepted_notification.eml.twig',
                    $context,
                    $member->getUser()
                );
            }
        }
    }

    /**
     * Send confirmation and notification email after a member declines
     *
     * @param CompanyMember $companyMember
     */
    public function sendInviteDeclinedEmails(CompanyMember $companyMember)
    {
        $context = [
            'memberWhoDeclined' => $companyMember,
            'company'           => $companyMember->getCompany(),
        ];

        // confirmation email to the user
        $this->sendMessage(
            'SnapRapidApiBundle:Emails:Company/member_invite_declined_confirmation.eml.twig',
            $context,
            $companyMember->getMatchingUser()
        );

        // notification emails to other company admin members
        foreach ($companyMember->getCompany()->getCompanyAdminUserMembers() as $member) {
            if ($member->getUser()->getId() != $companyMember->getMatchingUser()->getId()) {
                $context['user'] = $member->getUser();
                $this->sendMessage(
                    'SnapRapidApiBundle:Emails:Company/member_invite_declined_notification.eml.twig',
                    $context,
                    $member->getUser()
                );
            }
        }
    }

    /**
     * Send notification emails to members when a member is removed from the company
     *
     * @param CompanyMember $companyMember
     */
    public function sendMemberRemovedEmails(CompanyMember $companyMember)
    {
        $context = [
            'memberWhoWasRemoved' => $companyMember,
            'company'             => $companyMember->getCompany(),
        ];

        // notification emails to any other admins
        foreach ($companyMember->getCompany()->getCompanyAdminUserMembers() as $member) {
            $user = $member->getUser();
            if (!$companyMember->getUser() || $user->getId() != $companyMember->getUser()->getId()) {
                $context['user'] = $user;
                $this->sendMessage(
                    'SnapRapidApiBundle:Emails:Company/member_removed_notification.eml.twig',
                    $context,
                    $user
                );
            }
        }
    }
}
