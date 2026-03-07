<?php

namespace Plugins\Greeting\Controllers;

use App\Core\SettingsManager;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Greeting\GreetingContentResolver;

class GreetingController extends Controller
{
    /** @var GreetingContentResolver[] */
    protected array $resolvers;

    public function __construct(protected SettingsManager $settings)
    {
        $this->resolvers = [];
    }

    /**
     * Register a content resolver (called by other plugins).
     */
    public function addResolver(GreetingContentResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
        usort($this->resolvers, fn ($a, $b) => $a->priority() <=> $b->priority());
    }

    public function show(Request $request): JsonResponse
    {
        $user     = $request->user();
        $settings = $this->getSettings();

        if (! ($settings['enabled'] ?? true)) {
            return response()->json(['greeting' => null]);
        }

        // Build greeting message with tokens
        $message = $this->buildMessage($settings, $user);

        // Collect content blocks from resolvers
        $blocks = [];
        foreach ($this->resolvers as $resolver) {
            $block = $resolver->resolve($user);
            if ($block) {
                $blocks[] = $block;
            }
        }

        return response()->json([
            'greeting' => [
                'message'   => $message,
                'style'     => $settings['style'] ?? 'modal',
                'frequency' => $settings['frequency'] ?? 'once_per_day',
                'blocks'    => $blocks,
            ],
        ]);
    }

    public function adminSettings(Request $request): JsonResponse
    {
        return response()->json($this->getSettings());
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'enabled'           => 'boolean',
            'style'             => 'in:modal,banner,sidebar',
            'message_morning'   => 'nullable|string|max:300',
            'message_afternoon' => 'nullable|string|max:300',
            'message_evening'   => 'nullable|string|max:300',
            'frequency'         => 'in:every_login,once_per_day,once_per_session',
            'show_verse'        => 'boolean',
            'show_events'       => 'boolean',
            'show_prayer'       => 'boolean',
        ]);

        $this->settings->set('greeting_settings', array_merge(
            $this->getSettings(),
            $request->only([
                'enabled', 'style', 'message_morning', 'message_afternoon',
                'message_evening', 'frequency', 'show_verse', 'show_events', 'show_prayer',
            ])
        ));

        return response()->json(['message' => 'Greeting settings updated.']);
    }

    // -------------------------------------------------------------------------

    protected function getSettings(): array
    {
        return $this->settings->get('greeting_settings', [
            'enabled'           => true,
            'style'             => 'modal',
            'message_morning'   => 'Good morning, {first_name}! Blessings to you today.',
            'message_afternoon' => 'Good afternoon, {first_name}! Hope your day is going well.',
            'message_evening'   => 'Good evening, {first_name}! May God\'s peace be with you.',
            'frequency'         => 'once_per_day',
            'show_verse'        => true,
            'show_events'       => true,
            'show_prayer'       => false,
        ]);
    }

    protected function buildMessage(array $settings, $user): string
    {
        $hour = (int) Carbon::now()->format('H');

        $template = match (true) {
            $hour < 12  => $settings['message_morning']   ?? 'Welcome, {first_name}!',
            $hour < 17  => $settings['message_afternoon'] ?? 'Welcome, {first_name}!',
            default     => $settings['message_evening']   ?? 'Welcome, {first_name}!',
        };

        $firstName = explode(' ', $user->name)[0];
        $dayOfWeek = Carbon::now()->format('l');

        return str_replace(
            ['{first_name}', '{day_of_week}', '{name}'],
            [$firstName, $dayOfWeek, $user->name],
            $template
        );
    }
}
