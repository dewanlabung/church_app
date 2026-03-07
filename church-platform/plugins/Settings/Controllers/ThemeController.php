<?php

namespace Plugins\Settings\Controllers;

use App\Core\ThemeManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function __construct(protected ThemeManager $themes) {}

    /** Public: return compiled CSS custom properties. */
    public function css(): \Illuminate\Http\Response
    {
        return response($this->themes->css(), 200, [
            'Content-Type'  => 'text/css',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** Public: return active theme manifest. */
    public function manifest(): JsonResponse
    {
        return response()->json($this->themes->activeManifest());
    }

    /** Admin: list all installed themes. */
    public function index(): JsonResponse
    {
        return response()->json(['themes' => $this->themes->all()]);
    }

    /** Admin: activate a theme. */
    public function activate(Request $request): JsonResponse
    {
        $request->validate(['slug' => 'required|string']);

        if (! $this->themes->activate($request->slug)) {
            return response()->json(['message' => 'Theme not found.'], 404);
        }

        return response()->json(['message' => "Theme '{$request->slug}' activated."]);
    }

    /** Admin: install theme from zip upload. */
    public function install(Request $request): JsonResponse
    {
        $request->validate(['theme' => 'required|file|mimes:zip|max:20480']);

        $path = $request->file('theme')->getPathname();

        if (! $this->themes->install($path)) {
            return response()->json(['message' => 'Failed to install theme. Invalid zip.'], 422);
        }

        return response()->json(['message' => 'Theme installed successfully.']);
    }

    /** Admin: remove a theme. */
    public function remove(string $slug): JsonResponse
    {
        if (! $this->themes->remove($slug)) {
            return response()->json(['message' => 'Cannot remove active or default theme.'], 422);
        }

        return response()->json(['message' => "Theme '{$slug}' removed."]);
    }
}
