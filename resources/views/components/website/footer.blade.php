@php
  $footerLinks = [
    'Platform' => [
      ['label' => 'Features', 'href' => route('home') . '#features'],
      ['label' => 'Admissions', 'href' => route('home') . '#features'],
      ['label' => 'Results', 'href' => route('home') . '#features'],
      ['label' => 'Fees', 'href' => route('home') . '#features'],
    ],
    'Company' => [
      ['label' => 'About', 'href' => route('home') . '#why-edumanager'],
      ['label' => 'Support', 'href' => route('home') . '#support'],
      ['label' => 'Partner with us', 'href' => route('partner-registration-requests.create')],
      ['label' => 'Privacy Policy', 'href' => route('privacy-policy')],
    ],
  ];
@endphp

<footer class="site-footer">
  <div class="site-container site-footer__grid">
    <div class="site-footer__brand">
      <a class="brand brand--footer" href="{{ route('home') }}" aria-label="EduManager home">
        <img class="brand__logo" src="{{ asset('img/logo.png') }}" alt="EduManager logo" />
        <span class="brand__name">EduManager</span>
      </a>
      <p>
        A complete digital school management platform for administrators, teachers, parents, and students.
      </p>
      <a class="site-footer__contact" href="mailto:support@edumanager.ng">support@edumanager.ng</a>
      <a class="site-footer__contact" href="tel:+2349035316014">09035316014</a>
    </div>

    @foreach ($footerLinks as $heading => $links)
      <div class="site-footer__links">
        <h2>{{ $heading }}</h2>
        @foreach ($links as $link)
          <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
        @endforeach
      </div>
    @endforeach

    <div class="site-footer__cta">
      <h2>Ready to modernize your school?</h2>
      <p>Start with EduManager and bring records, payments, results, communication, and daily operations into one platform.</p>
      <a class="btn btn--primary" href="{{ route('registration-requests.create') }}">Subscribe Now</a>
    </div>
  </div>

  <div class="site-container site-footer__bottom">
    <p>&copy; {{ date('Y') }} EduManager. All rights reserved.</p>
    <p>Built for reliable school automation and digital education management.</p>
  </div>
</footer>
