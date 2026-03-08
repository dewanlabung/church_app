<?php

namespace Plugins\Sitemap\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $appUrl  = config('app.url');
        $sitemaps = [
            ['url' => $appUrl . '/sitemap-pages.xml',      'lastmod' => now()->toAtomString()],
            ['url' => $appUrl . '/sitemap-churches.xml',   'lastmod' => now()->toAtomString()],
            ['url' => $appUrl . '/sitemap-communities.xml','lastmod' => now()->toAtomString()],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        foreach ($sitemaps as $s) {
            $xml .= "  <sitemap><loc>{$s['url']}</loc><lastmod>{$s['lastmod']}</lastmod></sitemap>" . PHP_EOL;
        }
        $xml .= '</sitemapindex>';

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function churches()
    {
        $appUrl = config('app.url');
        $rows   = DB::table('church_pages')
            ->whereNull('deleted_at')
            ->select('slug', 'updated_at')
            ->orderBy('id')
            ->get();

        return $this->buildUrlset($appUrl, $rows, fn($r) => "/church/{$r->slug}", $rows->max('updated_at'));
    }

    public function communities()
    {
        $appUrl = config('app.url');
        $rows   = DB::table('communities')
            ->whereNull('deleted_at')
            ->where('type', 'public')
            ->select('slug', 'updated_at')
            ->orderBy('id')
            ->get();

        return $this->buildUrlset($appUrl, $rows, fn($r) => "/c/{$r->slug}", $rows->max('updated_at'));
    }

    public function pages()
    {
        $appUrl   = config('app.url');
        $static   = ['/', '/events', '/communities', '/search', '/prayer', '/bible-studies'];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($static as $path) {
            $xml .= "  <url><loc>{$appUrl}{$path}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>" . PHP_EOL;
        }

        $xml .= '</urlset>';
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    protected function buildUrlset(string $appUrl, $rows, callable $pathFn, ?string $lastmod): \Illuminate\Http\Response
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($rows as $r) {
            $loc     = $appUrl . $pathFn($r);
            $moddate = $r->updated_at ? date('Y-m-d', strtotime($r->updated_at)) : now()->toDateString();
            $xml .= "  <url><loc>{$loc}</loc><lastmod>{$moddate}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>" . PHP_EOL;
        }

        $xml .= '</urlset>';
        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
