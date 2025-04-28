<?php

namespace RRZE\Settings\Mail;

defined('ABSPATH') || exit;

/**
 * Class PHPMailer
 *
 * This class handles the PHPMailer functionality for the plugin.
 *
 * @package RRZE\Settings\Mail
 */
class PHPMailer
{
    /**
     * Site options
     * 
     * @var object
     */
    protected $siteOptions;

    /**
     * Constructor
     * 
     * @param object $siteOptions Site options
     */
    public function __construct($siteOptions)
    {
        $this->siteOptions = $siteOptions;
    }

    /**
     * Plugin loaded action
     * 
     * @return void
     */
    public function loaded()
    {
        add_action('phpmailer_init', [$this, 'phpMailerInit'], 999);
    }

    /**
     * Initialize PHPMailer
     * 
     * @param object $mailer The PHPMailer instance
     * @return void
     */
    public function phpMailerInit($mailer)
    {
        $sender = $mailer->Sender;

        if ($sender === '') {
            $sender = get_option('admin_email');
        }

        $parts = explode('@', $sender);
        $domain = array_pop($parts);
        $allowedDomains = (array) $this->siteOptions->mail->allowed_domains;

        if (
            !filter_var($sender, FILTER_VALIDATE_EMAIL)
            || (!empty($allowedDomains) && !in_array($domain, $allowedDomains))
        ) {
            $sender = $this->siteOptions->mail->sender;
        }

        $mailer->Sender = $sender;

        // Fetch the URL and site ID
        $url = get_site_url();
        $blogId = get_current_blog_id();

        // Add the custom header
        $mailer->addCustomHeader("X-CMS-Website: $url; $blogId");
    }
}
