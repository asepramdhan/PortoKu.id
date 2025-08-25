<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}" class="h-full">
    <head>
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
