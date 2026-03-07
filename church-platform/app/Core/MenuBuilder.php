<?php

namespace App\Core;

use Illuminate\Support\Facades\Cache;

class MenuBuilder
{
    protected const CACHE_TTL = 1800;

    protected SettingsManager $settings;

    public function __construct(SettingsManager $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get a menu by location.
     *
     * Locations: primary, mobile, auth_dropdown, admin_sidebar
     */
    public function get(string $location, ?string $userRole = null): array
    {
        $cacheKey = "menu_{$location}_{$userRole}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($location, $userRole) {
            $allMenus = $this->settings->get('menu_configs', []);
            $items    = $allMenus[$location] ?? $this->defaults($location);

            return $this->filterByRole($items, $userRole);
        });
    }

    /**
     * Save a menu configuration for a location.
     */
    public function save(string $location, array $items): void
    {
        $allMenus            = $this->settings->get('menu_configs', []);
        $allMenus[$location] = $items;
        $this->settings->set('menu_configs', $allMenus);
        $this->flush($location);
    }

    /**
     * Flush menu cache for a location (or all).
     */
    public function flush(?string $location = null): void
    {
        $roles = ['super_admin', 'church_admin', 'counsellor', 'musician', 'general_user', null];

        if ($location) {
            foreach ($roles as $role) {
                Cache::forget("menu_{$location}_{$role}");
            }
            return;
        }

        foreach (['primary', 'mobile', 'auth_dropdown', 'admin_sidebar'] as $loc) {
            foreach ($roles as $role) {
                Cache::forget("menu_{$loc}_{$role}");
            }
        }
    }

    /**
     * Filter items by role visibility.
     */
    protected function filterByRole(array $items, ?string $userRole): array
    {
        return array_values(array_filter(
            array_map(function ($item) use ($userRole) {
                // If item has role restriction and user doesn't match, skip
                $visibleTo = $item['visible_to'] ?? [];
                if (! empty($visibleTo) && $userRole && ! in_array($userRole, $visibleTo)) {
                    return null;
                }

                // Recursively filter children
                if (! empty($item['children'])) {
                    $item['children'] = $this->filterByRole($item['children'], $userRole);
                }

                return $item;
            }, $items)
        ));
    }

    /**
     * Default menu structures (used when no custom config saved).
     */
    protected function defaults(string $location): array
    {
        return match ($location) {
            'primary' => [
                ['label' => 'Home',        'url' => '/',           'icon' => 'home'],
                ['label' => 'Feed',        'url' => '/feed',       'icon' => 'layout'],
                ['label' => 'Churches',    'url' => '/churches',   'icon' => 'church'],
                ['label' => 'Communities', 'url' => '/community',  'icon' => 'users'],
                ['label' => 'Events',      'url' => '/events',     'icon' => 'calendar'],
                ['label' => 'Bible Study', 'url' => '/bible-study','icon' => 'book-open'],
            ],
            'mobile' => [
                ['label' => 'Home',    'url' => '/',        'icon' => 'home'],
                ['label' => 'Feed',    'url' => '/feed',    'icon' => 'layout'],
                ['label' => 'Search',  'url' => '/search',  'icon' => 'search'],
                ['label' => 'Prayer',  'url' => '/prayer',  'icon' => 'heart'],
                ['label' => 'Profile', 'url' => '/profile', 'icon' => 'user'],
            ],
            'auth_dropdown' => [
                ['label' => 'Profile',  'url' => '/profile',       'icon' => 'user'],
                ['label' => 'Settings', 'url' => '/settings',      'icon' => 'settings'],
                ['label' => 'divider'],
                ['label' => 'Admin',    'url' => '/admin',          'icon' => 'shield', 'visible_to' => ['super_admin', 'church_admin']],
                ['label' => 'divider'],
                ['label' => 'Logout',   'url' => '/logout',         'icon' => 'log-out'],
            ],
            'admin_sidebar' => [
                ['label' => 'Dashboard',    'url' => '/admin',               'icon' => 'layout-dashboard'],
                ['label' => 'Users',        'url' => '/admin/users',         'icon' => 'users'],
                ['label' => 'Churches',     'url' => '/admin/churches',      'icon' => 'church'],
                ['label' => 'Communities',  'url' => '/admin/communities',   'icon' => 'users-round'],
                ['label' => 'Posts',        'url' => '/admin/posts',         'icon' => 'file-text'],
                ['label' => 'Events',       'url' => '/admin/events',        'icon' => 'calendar'],
                ['label' => 'Bible Studies','url' => '/admin/bible-studies', 'icon' => 'book-open'],
                ['label' => 'Verses',       'url' => '/admin/verses',        'icon' => 'quote'],
                ['label' => 'divider'],
                ['label' => 'Plugins',      'url' => '/admin/plugins',       'icon' => 'puzzle'],
                ['label' => 'Themes',       'url' => '/admin/themes',        'icon' => 'palette'],
                ['label' => 'Menus',        'url' => '/admin/menus',         'icon' => 'menu'],
                ['label' => 'Settings',     'url' => '/admin/settings',      'icon' => 'settings'],
            ],
            default => [],
        };
    }
}
