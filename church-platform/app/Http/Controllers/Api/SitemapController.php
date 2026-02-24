<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Page;
use App\Models\Sermon;
use App\Models\Book;
use App\Models\BibleStudy;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Ministry;
use App\Models\Testimony;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = url('/');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $xml .= $this->urlEntry($baseUrl, now()->toDateString(), 'daily', '1.0');

        // Static pages
        $staticPages = ['events', 'prayers', 'library', 'studies', 'sermons', 'giving', 'ministries', 'reviews', 'testimonies', 'contact', 'about'];
        foreach ($staticPages as $page) {
            $xml .= $this->urlEntry($baseUrl . '/#' . $page, now()->toDateString(), 'weekly', '0.5');
        }

        // Posts
        $posts = Post::where('status', 'published')->get();
        foreach ($posts as $post) {
            $xml .= $this->urlEntry($baseUrl . '/post/' . $post->slug, $post->updated_at->toDateString(), 'weekly', '0.8');
        }

        // Pages
        if (class_exists(Page::class)) {
            $pages = Page::where('status', 'published')->get();
            foreach ($pages as $page) {
                $xml .= $this->urlEntry($baseUrl . '/page/' . $page->slug, $page->updated_at->toDateString(), 'weekly', '0.7');
            }
        }

        // Sermons
        $sermons = Sermon::where('is_active', true)->get();
        foreach ($sermons as $sermon) {
            $xml .= $this->urlEntry($baseUrl . '/sermon/' . $sermon->slug, $sermon->updated_at->toDateString(), 'weekly', '0.7');
        }

        // Events
        $events = Event::where('is_active', true)->get();
        foreach ($events as $event) {
            $xml .= $this->urlEntry($baseUrl . '/event/' . $event->slug, $event->updated_at->toDateString(), 'weekly', '0.6');
        }

        // Books
        $books = Book::where('is_active', true)->get();
        foreach ($books as $book) {
            $xml .= $this->urlEntry($baseUrl . '/book/' . $book->slug, $book->updated_at->toDateString(), 'monthly', '0.6');
        }

        // Bible Studies
        $studies = BibleStudy::where('is_active', true)->get();
        foreach ($studies as $study) {
            $xml .= $this->urlEntry($baseUrl . '/bible-study/' . $study->slug, $study->updated_at->toDateString(), 'monthly', '0.6');
        }

        // Ministries
        $ministries = Ministry::where('is_active', true)->get();
        foreach ($ministries as $ministry) {
            $xml .= $this->urlEntry($baseUrl . '/ministry/' . $ministry->slug, $ministry->updated_at->toDateString(), 'monthly', '0.5');
        }

        // Testimonies
        $testimonies = Testimony::whereIn('status', ['approved', 'featured'])->get();
        foreach ($testimonies as $testimony) {
            $xml .= $this->urlEntry($baseUrl . '/testimony/' . $testimony->slug, $testimony->updated_at->toDateString(), 'monthly', '0.6');
        }

        // Galleries
        if (class_exists(Gallery::class)) {
            $galleries = Gallery::where('is_active', true)->get();
            foreach ($galleries as $gallery) {
                $xml .= $this->urlEntry($baseUrl . '/gallery/' . ($gallery->slug ?? $gallery->id), $gallery->updated_at->toDateString(), 'monthly', '0.5');
            }
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function urlEntry(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n" .
               "    <loc>" . htmlspecialchars($loc) . "</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }
}
