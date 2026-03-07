<?php

namespace Plugins\Auth\Controllers;

use App\Core\SettingsManager;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(protected SettingsManager $settings) {}

    public function register(Request $request): JsonResponse
    {
        // Check if registration is open
        if (! $this->settings->get('auth_registration_open', true)) {
            return response()->json(['message' => 'Registration is currently closed.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(
                (int) $this->settings->get('auth_password_min_length', 8)
            )],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'user_type'     => 'general_user',
            'custom_fields' => [],
        ]);

        // Assign default role
        $user->assignRole('general_user');

        // Email confirmation
        if ($this->settings->get('auth_email_confirmation', false)) {
            event(new Registered($user));
            return response()->json([
                'message' => 'Registration successful. Please verify your email.',
            ], 201);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        if (! $user->email_verified_at && $this->settings->get('auth_email_confirmation', false)) {
            return response()->json(['message' => 'Please verify your email first.'], 403);
        }

        // Single device login: revoke existing tokens
        if ($this->settings->get('auth_single_device_login', false)) {
            $user->tokens()->delete();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->formatUser($request->user()));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name'   => 'sometimes|string|max:255',
            'bio'    => 'sometimes|nullable|string|max:1000',
            'phone'  => 'sometimes|nullable|string|max:30',
            'avatar' => 'sometimes|nullable|image|max:2048',
        ]);

        $data = $request->only(['name', 'bio', 'phone']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Merge custom fields
        if ($request->has('custom_fields')) {
            $data['custom_fields'] = array_merge(
                $user->custom_fields ?? [],
                $request->input('custom_fields', [])
            );
        }

        $user->update($data);

        return response()->json($this->formatUser($user->fresh()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->current_password, $request->user()->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $request->user()->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        if (! $this->settings->get('auth_allow_self_delete', true)) {
            return response()->json(['message' => 'Account deletion is disabled.'], 403);
        }

        $request->validate(['password' => 'required']);

        if (! Hash::check($request->password, $request->user()->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $request->user()->tokens()->delete();
        $request->user()->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    public function verifyEmail(string $token): JsonResponse
    {
        $user = User::where('email_verification_token', $token)->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired token.'], 404);
        }

        $user->update([
            'email_verified_at'          => now(),
            'email_verification_token'   => null,
        ]);

        return response()->json(['message' => 'Email verified.']);
    }

    public function greeting(Request $request): JsonResponse
    {
        // Greeting is resolved by the Greeting plugin's resolver chain
        // If Greeting plugin is disabled, return empty
        if (! app()->bound('greeting.resolver')) {
            return response()->json(['greeting' => null]);
        }

        return response()->json(app('greeting.resolver')->resolve($request->user()));
    }

    // OAuth
    public function oauthRedirect(string $provider): JsonResponse
    {
        $enabledKey = "auth_{$provider}_enabled";
        if (! $this->settings->get($enabledKey, false)) {
            return response()->json(['message' => "OAuth provider {$provider} is not enabled."], 404);
        }

        $url = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
        return response()->json(['redirect_url' => $url]);
    }

    public function oauthCallback(Request $request, string $provider): JsonResponse
    {
        try {
            $oauthUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['message' => 'OAuth authentication failed.'], 401);
        }

        $user = User::firstOrCreate(
            ['email' => $oauthUser->getEmail()],
            [
                'name'              => $oauthUser->getName(),
                'password'          => Hash::make(str()->random(32)),
                'email_verified_at' => now(),
                'provider'          => $provider,
                'provider_id'       => $oauthUser->getId(),
                'avatar'            => $oauthUser->getAvatar(),
                'user_type'         => 'general_user',
            ]
        );

        if (! $user->hasRole('general_user')) {
            $user->assignRole('general_user');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    protected function formatUser(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'avatar'            => $user->avatar ? asset("storage/{$user->avatar}") : null,
            'bio'               => $user->bio,
            'phone'             => $user->phone,
            'user_type'         => $user->user_type,
            'roles'             => $user->getRoleNames(),
            'permissions'       => $user->getAllPermissions()->pluck('name'),
            'custom_fields'     => $user->custom_fields ?? [],
            'email_verified_at' => $user->email_verified_at,
            'created_at'        => $user->created_at,
        ];
    }
}
