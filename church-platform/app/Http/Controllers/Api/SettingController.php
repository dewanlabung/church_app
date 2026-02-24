<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    /**
     * Display the church settings.
     */
    public function show(): JsonResponse
    {
        $setting = Setting::first();

        if (!$setting) {
            return response()->json([
                'success' => true,
                'message' => 'No settings configured yet.',
                'data'    => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $setting,
        ]);
    }

    /**
     * Update the church settings (create if not exists).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'church_name'       => 'sometimes|required|string|max:255',
            'tagline'           => 'nullable|string|max:500',
            'description'       => 'nullable|string|max:5000',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:30',
            'address'           => 'nullable|string|max:500',
            'city'              => 'nullable|string|max:255',
            'state'             => 'nullable|string|max:255',
            'zip_code'          => 'nullable|string|max:20',
            'country'           => 'nullable|string|max:255',
            'website_url'       => 'nullable|url|max:500',
            'facebook_url'      => 'nullable|url|max:500',
            'twitter_url'       => 'nullable|url|max:500',
            'instagram_url'     => 'nullable|url|max:500',
            'youtube_url'       => 'nullable|url|max:500',
            'tiktok_url'        => 'nullable|url|max:500',
            'service_times'     => 'nullable|string|max:2000',
            'pastor_name'       => 'nullable|string|max:255',
            'pastor_title'      => 'nullable|string|max:255',
            'about_text'        => 'nullable|string|max:10000',
            'mission_statement' => 'nullable|string|max:2000',
            'vision_statement'  => 'nullable|string|max:2000',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'banner'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'favicon'           => 'nullable|image|mimes:ico,png|max:1024',
            'primary_color'     => 'nullable|string|max:20',
            'secondary_color'   => 'nullable|string|max:20',
            'footer_text'       => 'nullable|string|max:1000',
            'google_maps_embed' => 'nullable|string|max:2000',
            'meta_title'        => 'nullable|string|max:70',
            'meta_description'  => 'nullable|string|max:160',
            'meta_keywords'     => 'nullable|string|max:500',
            'theme_config'      => 'nullable|json|max:10000',
            'widget_config'     => 'nullable|json|max:10000',
        ]);

        // Decode JSON strings to arrays for proper storage
        if (isset($validated['theme_config']) && is_string($validated['theme_config'])) {
            $validated['theme_config'] = json_decode($validated['theme_config'], true);
        }
        if (isset($validated['widget_config']) && is_string($validated['widget_config'])) {
            $validated['widget_config'] = json_decode($validated['widget_config'], true);
        }

        $setting = Setting::first();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($setting && $setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $validated['logo'] = $request->file('logo')
                ->store('settings', 'public');
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            if ($setting && $setting->banner) {
                Storage::disk('public')->delete($setting->banner);
            }
            $validated['banner'] = $request->file('banner')
                ->store('settings', 'public');
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            if ($setting && $setting->favicon) {
                Storage::disk('public')->delete($setting->favicon);
            }
            $validated['favicon'] = $request->file('favicon')
                ->store('settings', 'public');
        }

        if ($setting) {
            $setting->update($validated);
            $message = 'Settings updated successfully.';
        } else {
            $setting = Setting::create($validated);
            $message = 'Settings created successfully.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $setting->fresh(),
        ]);
    }

    /**
     * Get email settings (admin only, returns masked secrets).
     */
    public function emailSettings(): JsonResponse
    {
        $setting = Setting::first();

        return response()->json([
            'success' => true,
            'data'    => $setting ? $setting->getEmailSettings() : [
                'mail_provider' => 'smtp',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'email_contact_notification' => true,
                'email_newsletter_enabled' => true,
                'email_welcome_enabled' => false,
            ],
        ]);
    }

    /**
     * Update email settings.
     */
    public function updateEmailSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mail_provider'              => 'required|string|in:smtp,mailchimp,sendgrid,mailgun',
            'smtp_host'                  => 'nullable|string|max:255',
            'smtp_port'                  => 'nullable|integer|min:1|max:65535',
            'smtp_username'              => 'nullable|string|max:255',
            'smtp_password'              => 'nullable|string|max:500',
            'smtp_encryption'            => 'nullable|string|in:tls,ssl,none',
            'mail_from_address'          => 'nullable|email|max:255',
            'mail_from_name'             => 'nullable|string|max:255',
            'mailchimp_api_key'          => 'nullable|string|max:500',
            'mailchimp_list_id'          => 'nullable|string|max:255',
            'mailchimp_server_prefix'    => 'nullable|string|max:20',
            'sendgrid_api_key'           => 'nullable|string|max:500',
            'mailgun_domain'             => 'nullable|string|max:255',
            'mailgun_secret'             => 'nullable|string|max:500',
            'email_contact_notification' => 'boolean',
            'email_contact_recipient'    => 'nullable|email|max:255',
            'email_newsletter_enabled'   => 'boolean',
            'email_welcome_enabled'      => 'boolean',
            'email_welcome_template'     => 'nullable|string|max:10000',
            'email_signature'            => 'nullable|string|max:2000',
        ]);

        $setting = Setting::first();

        // Don't overwrite secrets if placeholder values sent
        if (isset($validated['smtp_password']) && empty($validated['smtp_password'])) {
            unset($validated['smtp_password']);
        }
        if (isset($validated['mailchimp_api_key']) && empty($validated['mailchimp_api_key'])) {
            unset($validated['mailchimp_api_key']);
        }
        if (isset($validated['sendgrid_api_key']) && empty($validated['sendgrid_api_key'])) {
            unset($validated['sendgrid_api_key']);
        }
        if (isset($validated['mailgun_secret']) && empty($validated['mailgun_secret'])) {
            unset($validated['mailgun_secret']);
        }

        if ($setting) {
            $setting->update($validated);
        } else {
            $setting = Setting::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email settings updated successfully.',
            'data'    => $setting->fresh()->getEmailSettings(),
        ]);
    }

    /**
     * Send a test email to verify email configuration.
     */
    public function testEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_email' => 'required|email|max:255',
        ]);

        try {
            $emailService = new EmailService();
            $emailService->sendTestEmail($validated['to_email']);

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $validated['to_email'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }
}
