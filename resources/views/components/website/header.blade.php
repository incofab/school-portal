@php
  $navigationItems = [
    ['label' => 'Features', 'href' => route('home') . '#features'],
    ['label' => 'Why EduManager', 'href' => route('home') . '#why-edumanager'],
    ['label' => 'Trust', 'href' => route('home') . '#trust'],
    ['label' => 'Support', 'href' => route('home') . '#support'],
  ];
@endphp

<header class="site-header" aria-label="Primary website navigation">
  <div class="site-container site-header__inner">
    <a class="brand" href="{{ route('home') }}" aria-label="EduManager home">
      <img class="brand__logo" src="{{ asset('img/logo.png') }}" alt="EduManager logo" />
      <span class="brand__name">EduManager</span>
    </a>

    <nav class="site-nav" aria-label="Primary navigation">
      @foreach ($navigationItems as $item)
        <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
      @endforeach
    </nav>

    <div class="site-header__actions">
      <a class="btn btn--ghost" href="{{ route('login') }}">Login</a>
      <a class="btn btn--primary" href="{{ route('registration-requests.create') }}">Get Started</a>
    </div>
  </div>
</header>
