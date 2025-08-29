<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}" class="h-full">
    <head>
        <link
            rel="icon"
            type="image/png"
            href="{{ asset("favicon-96x96.png") }}"
            sizes="96x96"
        />
        <link
            rel="icon"
            type="image/svg+xml"
            href="{{ asset("favicon.svg") }}"
        />
        <link rel="shortcut icon" href="{{ asset("favicon.ico") }}" />
        <link
            rel="apple-touch-icon"
            sizes="180x180"
            href="{{ asset("apple-touch-icon.png") }}"
        />
        <meta name="apple-mobile-web-app-title" content="MyWebSite" />
        <link rel="manifest" href="{{ asset("site.webmanifest") }}" />
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $title ?? "Admin Chat - PortoKu.id" }}</title>

        @vite(["resources/css/app.css", "resources/js/app.js"])
    </head>
    <body>
        <div class="min-h-screen flex flex-col bg-slate-900">
            {{ $slot }}
        </div>
    </body>
</html>
