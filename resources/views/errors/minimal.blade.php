<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        @vite('resources/css/minimal.css')
    </head>
    <body class="antialiased">
    <main class="grid min-h-screen place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8">
        <div class="text-center">
            <p class="text-base font-semibold text-indigo-600">
                @yield('code')

            </p>
            <h1 class="mt-4 text-5xl font-semibold tracking-tight text-balance text-gray-900 sm:text-7xl">@yield('title')</h1>
            <p class="mt-6 text-lg font-medium text-pretty text-gray-500 sm:text-xl/8">@yield('message')</p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <a href="{{ route('pages.welcome') }}" class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Go back home</a>
                <a href="{{ route('pages.welcome') }}" class="text-sm font-semibold text-gray-900">Contact support <span aria-hidden="true">&rarr;</span></a>
            </div>
        </div>
    </main>
    </body>
</html>

