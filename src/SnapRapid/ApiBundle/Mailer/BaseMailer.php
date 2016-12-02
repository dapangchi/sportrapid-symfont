<?php

namespace SnapRapid\ApiBundle\Mailer;

use SnapRapid\Core\Model\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BaseMailer
{
    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var string
     */
    private $websiteUrl;

    /**
     * @var array
     */
    protected $addresses;

    /**
     * @param \Swift_Mailer         $mailer
     * @param UrlGeneratorInterface $router
     * @param \Twig_Environment     $twig
     * @param string                $websiteUrl
     * @param array                 $addresses
     */
    public function __construct(
        \Swift_Mailer $mailer,
        UrlGeneratorInterface $router,
        \Twig_Environment $twig,
        $websiteUrl,
        array $addresses
    ) {
        $this->mailer     = $mailer;
        $this->router     = $router;
        $this->twig       = $twig;
        $this->websiteUrl = $websiteUrl;
        $this->addresses  = $addresses;
    }

    /**
     * Flush the mail queue
     */
    public function flushQueue()
    {
        $spool     = $this->mailer->getTransport()->getSpool();
        $transport = $this->container->get('swiftmailer.transport.real');
        $spool->flushQueue($transport);
        $transport->stop();
    }

    /**
     * Flush the local (sendmail) mailer queue
     */
    public function flushLocalMailerQueue()
    {
        $mailer    = $this->container->get('swiftmailer.mailer.local');
        $spool     = $mailer->getTransport()->getSpool();
        $transport = $this->container->get('swiftmailer.mailer.local.transport.real');
        $spool->flushQueue($transport);
        $transport->stop();
    }

    /**
     * Get to address from a user object
     *
     * @param User $user
     *
     * @return string
     */
    protected function getTo(User $user)
    {
        return [$user->getEmail() => $user->getFullName()];
    }

    /**
     * Get the notifications sender
     *
     * @return array
     */
    protected function getNotificationsAddress()
    {
        return [$this->addresses['notifications']['email'] => $this->addresses['notifications']['name']];
    }

    /**
     * Get the contact sender
     *
     * @return array
     */
    protected function getContactAddress()
    {
        return [$this->addresses['contact']['email'] => $this->addresses['contact']['name']];
    }

    /**
     * Send a message
     *
     * @param $templateName
     * @param $context
     * @param $toUser User
     * @param $from
     */
    protected function sendMessage($templateName, $context, User $toUser, $from = 'notifications')
    {
        $fromEmail = $from == 'notifications' ? $this->getNotificationsAddress() : $this->getContactAddress();
        $toEmail   = $this->getTo($toUser);

        // add frontend website url
        $context['websiteUrl'] = $this->websiteUrl;

        // add noreply param - show no reply message if not sending to SnapRapid and not sending from the contact email
        $context['noreply'] = key($toEmail) != $this->addresses['notifications']['email']
            && key($toEmail) != $this->addresses['contact']['email']
            && key($fromEmail) != $this->addresses['contact']['email'];

        $message = $this->buildMessage($templateName, $context, $fromEmail, $toEmail);

        $this->mailer->send($message);
    }

    /**
     * Send a message to the admins
     *
     * @param string $templateName
     * @param array  $context
     * @param string $to
     * @param User   $fromUser
     */
    protected function sendAdminMessage($templateName, $context, $to = 'contact', User $fromUser = null)
    {
        $fromEmail = $this->getNotificationsAddress();
        $toEmail   = $to == 'notifications' ? $this->getNotificationsAddress() : $this->getContactAddress();
        $message   = $this->buildMessage($templateName, $context, $fromEmail, $toEmail);

        if ($fromUser) {
            $message->setReplyTo($fromUser->getEmail(), $fromUser->getFirstName());
        }

        $this->mailer->send($message);
    }

    /**
     * Build email message
     *
     * @param $templateName
     * @param $context
     * @param $fromEmail
     * @param $toEmail
     *
     * @return \Swift_Mime_SimpleMessage
     */
    private function buildMessage($templateName, $context, $fromEmail, $toEmail)
    {
        // set up params
        $template = $this->twig->loadTemplate($templateName);
        $subject  = $template->renderBlock('subject', $context);
        $textBody = $template->renderBlock('body_text', $context);
        $htmlBody = $template->renderBlock('body_html', $context);

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($fromEmail)
            ->setTo($toEmail);

        if (!empty($htmlBody)) {
            $message->setBody($htmlBody, 'text/html')
                ->addPart($textBody, 'text/plain');
        } else {
            $message->setBody($textBody);
        }

        return $message;
    }
}
