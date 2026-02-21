<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
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
        ]);

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
}
