<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'theme_config' => 'array',
        'widget_config' => 'array',
        'email_contact_notification' => 'boolean',
        'email_newsletter_enabled' => 'boolean',
        'email_welcome_enabled' => 'boolean',
    ];

    /**
     * Hide sensitive credentials from serialization.
     */
    protected $hidden = [
        'smtp_password', 'mailchimp_api_key', 'sendgrid_api_key', 'mailgun_secret',
    ];

    public static function get($key, $default = null)
    {
        $settings = static::first();
        return $settings ? ($settings->$key ?? $default) : $default;
    }

    /**
     * Get email settings with secrets masked (for admin UI).
     */
    public function getEmailSettings(): array
    {
        return [
            'mail_provider' => $this->mail_provider ?? 'smtp',
            'smtp_host' => $this->smtp_host,
            'smtp_port' => $this->smtp_port ?? 587,
            'smtp_username' => $this->smtp_username,
            'has_smtp_password' => !empty($this->smtp_password),
            'smtp_encryption' => $this->smtp_encryption ?? 'tls',
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name' => $this->mail_from_name,
            'has_mailchimp_api_key' => !empty($this->mailchimp_api_key),
            'mailchimp_list_id' => $this->mailchimp_list_id,
            'mailchimp_server_prefix' => $this->mailchimp_server_prefix,
            'has_sendgrid_api_key' => !empty($this->sendgrid_api_key),
            'has_mailgun_secret' => !empty($this->mailgun_secret),
            'mailgun_domain' => $this->mailgun_domain,
            'email_contact_notification' => $this->email_contact_notification ?? true,
            'email_contact_recipient' => $this->email_contact_recipient,
            'email_newsletter_enabled' => $this->email_newsletter_enabled ?? true,
            'email_welcome_enabled' => $this->email_welcome_enabled ?? false,
            'email_welcome_template' => $this->email_welcome_template,
            'email_signature' => $this->email_signature,
        ];
    }
}
