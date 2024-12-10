<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    @if (Request::is('') || Request::is('/'))
    <meta property="og:type" content="website">
    <meta property="og:title" content="Free Notion tools - Exnota Tools">
    <meta property="og:url" content="{{ url()->full() }}">
    <meta property="og:image" content="{{ Request::root() }}/images/social-1200x600.png">
    <meta property="og:description" content="Free Notion tools. Delete duplicates in a database, and more.">
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="tools.exnota.com">
    <meta property="twitter:url" content="{{ url()->full() }}">
    <meta name="twitter:title" content="Free Notion tools - Exnota Tools">
    <meta name="twitter:description" content="Free Notion tools. Delete duplicates in a database, and more.">
    <meta name="twitter:image" content="{{ Request::root() }}/images/social-1200x600.png">
    @elseif (Request::is('duplicates/*') || Request::is('duplicates'))
    <meta property="og:type" content="website">
    <meta property="og:title" content="Delete duplicates in Notion databases - Exnota Tools">
    <meta property="og:url" content="{{ url()->full() }}">
    <meta property="og:image" content="{{ Request::root() }}/images/duplicates/social-1200x600.png">
    <meta property="og:description" content="Delete duplicates in Notion databases, in just a few clicks.">
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="tools.exnota.com">
    <meta property="twitter:url" content="{{ url()->full() }}">
    <meta name="twitter:title" content="Delete duplicates in Notion databases - Exnota Tools">
    <meta name="twitter:description" content="Delete duplicates in Notion databases, in just a few clicks.">
    <meta name="twitter:image" content="{{ Request::root() }}/images/duplicates/social-1200x600.png">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead

    <!-- Umami analytics -->
    <script defer src="https://cloud.umami.is/script.js" data-website-id="282625de-ac77-4419-966f-23b9e88fbf83"></script>

    <!-- Fathom analytics -->
    <script src="https://cdn.usefathom.com/script.js" data-spa="auto" data-site="BBIKAHLJ" defer></script>

    <!-- crisp.chat -->
    <script type="text/javascript">
        window.$crisp = [];
        window.CRISP_WEBSITE_ID = "ce54fcea-7cfd-42ed-af5c-29d2512544d8";
        (function() {
            d = document;
            s = d.createElement("script");
            s.src = "https://client.crisp.chat/l.js";
            s.async = 1;
            d.getElementsByTagName("head")[0].appendChild(s);
        })();
    </script>
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>