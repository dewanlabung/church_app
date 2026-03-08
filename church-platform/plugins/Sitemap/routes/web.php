<?php

use Illuminate\Support\Facades\Route;
use Plugins\Sitemap\Controllers\SitemapController;

Route::get('/sitemap.xml',             [SitemapController::class, 'index']);
Route::get('/sitemap-pages.xml',       [SitemapController::class, 'pages']);
Route::get('/sitemap-churches.xml',    [SitemapController::class, 'churches']);
Route::get('/sitemap-communities.xml', [SitemapController::class, 'communities']);
