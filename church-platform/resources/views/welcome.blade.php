<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Church Platform') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-50 antialiased">
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-church text-white text-lg"></i>
                </div>
                <span class="text-xl font-bold text-gray-800">{{ config('app.name', 'Church Platform') }}</span>
            </div>
            <nav class="hidden md:flex items-center space-x-6">
                <a href="#about" class="text-gray-600 hover:text-indigo-600 text-sm font-medium transition-colors">About</a>
                <a href="#sermons" class="text-gray-600 hover:text-indigo-600 text-sm font-medium transition-colors">Sermons</a>
                <a href="#events" class="text-gray-600 hover:text-indigo-600 text-sm font-medium transition-colors">Events</a>
                <a href="#contact" class="text-gray-600 hover:text-indigo-600 text-sm font-medium transition-colors">Contact</a>
                @auth
                    <a href="/admin" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Admin Panel</a>
                @else
                    <a href="/login" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">Login</a>
                @endauth
            </nav>
        </div>
    </header>

    <section class="bg-gradient-to-br from-indigo-900 via-indigo-800 to-purple-900 text-white py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-6">Welcome to Our Church</h1>
            <p class="text-xl text-indigo-200 max-w-2xl mx-auto mb-8">A place of worship, community, and spiritual growth. Join us and be part of something wonderful.</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#contact" class="px-8 py-3 bg-white text-indigo-900 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-lg">Visit Us</a>
                <a href="#sermons" class="px-8 py-3 border-2 border-white text-white font-semibold rounded-xl hover:bg-white hover:text-indigo-900 transition-colors">Watch Sermons</a>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-400 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'Church Platform') }}. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
