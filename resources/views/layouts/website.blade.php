<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#008374" />

    <title>@yield('title', 'EduManager - School Management Software')</title>
    <meta
      name="description"
      content="@yield('meta_description', 'EduManager is a complete school management software for student records, admissions, attendance, fees, results, communication, and school automation.')"
    />
    <meta
      name="keywords"
      content="@yield('meta_keywords', 'school management software, school automation, student management system, digital school platform, EduManager')"
    />
    <link rel="canonical" href="@yield('canonical_url', url()->current())" />

    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="EduManager" />
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', 'EduManager - School Management Software')))" />
    <meta
      property="og:description"
      content="@yield('og_description', trim($__env->yieldContent('meta_description', 'EduManager is a complete school management software for modern schools.')))"
    />
    <meta property="og:url" content="@yield('canonical_url', url()->current())" />
    <meta property="og:image" content="@yield('og_image', asset('img/logo.png'))" />

    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="@yield('og_title', trim($__env->yieldContent('title', 'EduManager - School Management Software')))" />
    <meta
      name="twitter:description"
      content="@yield('og_description', trim($__env->yieldContent('meta_description', 'EduManager is a complete school management software for modern schools.')))"
    />
    <meta name="twitter:image" content="@yield('og_image', asset('img/logo.png'))" />

    <link rel="icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" href="{{ asset('img/logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="{{ asset('css/website.css') }}" />
    @stack('styles')
  </head>
  <body>
    <a class="skip-link" href="#main-content">Skip to content</a>

    <x-website.header />

    <main id="main-content">
      @yield('content')
    </main>

    <x-website.footer />

    @stack('scripts')
  </body>
</html>
