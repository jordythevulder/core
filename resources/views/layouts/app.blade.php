<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '')</title>
    <meta name="description" content="@yield('description', '')"/>
    <meta name="robots" content="@yield('robots', Rapidez::config('design/search_engine_robots/default_robots', 'INDEX,FOLLOW'))"/>

    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')" />
    @endif

    <link href="{{ url(mix('css/app.css')) }}" rel="preload" as="style">
    <link href="{{ url(mix('css/app.css')) }}" rel="stylesheet" data-turbolinks-track="reload">
    <script src="{{ url(mix('js/manifest.js')) }}" defer data-turbolinks-track="reload"></script>
    <script src="{{ url(mix('js/vendor.js')) }}" defer data-turbolinks-track="reload"></script>
    <script src="{{ url(mix('js/app.js')) }}" defer data-turbolinks-track="reload"></script>
    @stack('head')
</head>
<body class="bg-white antialiased">
    <div id="app">
        @include('rapidez::layouts.partials.header')
        <main class="mx-5">
            @yield('content')
        </main>
        @include('rapidez::layouts.partials.footer')
        @stack('page_end')
    </div>

    <script>window.config = @json(config('frontend'));</script>
</body>
</html>
