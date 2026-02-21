<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Display a paginated listing of newsletter subscribers (admin).
     */
    public function subscribers(Request $request): JsonResponse
    {
        $query = NewsletterSubscriber::query()->latest();

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $subscribers,
        ]);
    }

    /**
     * Subscribe to the newsletter (public).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name'  => 'nullable|string|max:255',
        ]);

        // Check if already subscribed
        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing) {
            if ($existing->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already subscribed to our newsletter.',
                ], 409);
            }

            // Re-activate if previously unsubscribed
            $existing->update([
                'is_active'     => true,
                'token'         => Str::random(64),
                'subscribed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Welcome back! You have been re-subscribed to our newsletter.',
                'data'    => $existing->fresh(),
            ]);
        }

        $subscriber = NewsletterSubscriber::create([
            'email'         => $validated['email'],
            'name'          => $validated['name'] ?? null,
            'token'         => Str::random(64),
            'is_active'     => true,
            'subscribed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing to our newsletter!',
            'data'    => $subscriber,
        ], 201);
    }

    /**
     * Unsubscribe from the newsletter using token (public).
     */
    public function unsubscribe(string $token): JsonResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid unsubscribe token.',
            ], 404);
        }

        if (!$subscriber->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already unsubscribed.',
            ], 400);
        }

        $subscriber->update([
            'is_active'       => false,
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You have been successfully unsubscribed from our newsletter.',
        ]);
    }
}
