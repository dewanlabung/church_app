<?php

namespace Plugins\Settings\Controllers;

use App\Core\PluginManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginManagerController extends Controller
{
    public function __construct(protected PluginManager $plugins) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'plugins' => array_values($this->plugins->all()),
        ]);
    }

    public function enable(string $slug): JsonResponse
    {
        if (! $this->plugins->enable($slug)) {
            return response()->json(['message' => "Plugin '{$slug}' not found."], 404);
        }

        return response()->json([
            'message' => "Plugin '{$slug}' enabled. Restart may be needed for full effect.",
            'plugin'  => $this->plugins->manifest($slug),
        ]);
    }

    public function disable(string $slug): JsonResponse
    {
        if (! $this->plugins->disable($slug)) {
            return response()->json(['message' => "Plugin '{$slug}' cannot be disabled (core plugin or not found)."], 422);
        }

        return response()->json(['message' => "Plugin '{$slug}' disabled."]);
    }

    public function remove(string $slug): JsonResponse
    {
        if (! $this->plugins->remove($slug)) {
            return response()->json(['message' => "Plugin '{$slug}' cannot be removed. Disable it first, or it's a protected plugin."], 422);
        }

        return response()->json(['message' => "Plugin '{$slug}' removed."]);
    }

    public function install(Request $request): JsonResponse
    {
        $request->validate(['plugin' => 'required|file|mimes:zip|max:51200']);

        $path = $request->file('plugin')->store('plugin-uploads', 'local');
        $fullPath = storage_path("app/{$path}");

        // Extract zip to plugins directory
        $zip = new \ZipArchive();
        if ($zip->open($fullPath) !== true) {
            return response()->json(['message' => 'Invalid zip file.'], 422);
        }

        $zip->extractTo(base_path('plugins'));
        $zip->close();

        \Illuminate\Support\Facades\Storage::disk('local')->delete($path);

        return response()->json([
            'message' => 'Plugin installed. It will appear in the plugin list as inactive.',
        ]);
    }
}
