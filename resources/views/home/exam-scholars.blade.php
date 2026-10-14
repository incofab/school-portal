@extends('layouts.website')

@section('title', 'Exam Scholars | Confident Exam Preparation')
@section('meta_description', 'Prepare for JAMB/UTME, WAEC/SSCE, BECE and NCEE with Exam Scholars: realistic CBT practice, detailed explanations, performance insights and offline access.')
@section('meta_keywords', 'Exam Scholars, JAMB CBT practice, WAEC past questions, exam preparation, offline CBT app, BECE, NCEE')
@section('canonical_url', route('exam-scholars'))

@section('content')
  <section class="scholars-hero">
    <div class="site-container scholars-hero__grid">
      <div class="scholars-hero__content">
        <a class="scholars-back-link" href="{{ route('home') }}">← EduManager home</a>
        <p class="eyebrow">A smarter way to prepare</p>
        <h1>Practise with purpose. Walk into exams with confidence.</h1>
        <p class="scholars-hero__lead">Exam Scholars helps learners prepare for JAMB/UTME, WAEC/SSCE, BECE and NCEE with rich past-question practice and a realistic computer-based exam experience.</p>
        <div class="scholars-hero__actions">
          <a class="btn btn--primary btn--large" href="https://examscholars.com" target="_blank" rel="noopener noreferrer">Explore Exam Scholars <span aria-hidden="true">↗</span></a>
          <a class="btn btn--secondary btn--large" href="https://examscholars.com/products/download-jamb-utme-waec-neco-bece-cbt-practice-software" target="_blank" rel="noopener noreferrer">Get the app</a>
        </div>
        <ul class="scholars-exam-list" aria-label="Supported exam categories">
          <li>JAMB / UTME</li><li>WAEC / SSCE</li><li>BECE</li><li>NCEE</li>
        </ul>
      </div>

      <div class="scholars-preview" aria-label="Illustration of the Exam Scholars practice experience">
        <div class="scholars-preview__top"><span class="scholars-preview__brand"><span>ES</span> Exam Scholars</span><span class="scholars-preview__badge">PRACTICE SESSION</span></div>
        <div class="scholars-preview__body">
          <div class="scholars-preview__meta"><span>Mathematics</span><span>Question 12 of 40</span></div>
          <div class="scholars-preview__progress"><span></span></div>
          <h2>If 3x + 5 = 20, what is the value of x?</h2>
          <ol class="scholars-preview__answers">
            <li><span>A</span> 3</li><li class="is-selected"><span>B</span> 5 <b aria-hidden="true">✓</b></li><li><span>C</span> 7</li><li><span>D</span> 15</li>
          </ol>
          <div class="scholars-preview__footer"><span>Study mode · Take your time</span><span>12:48</span></div>
        </div>
        <div class="scholars-preview__note"><span aria-hidden="true">✦</span><span><strong>Learn as you practise</strong><small>Review answers with clear explanations</small></span></div>
      </div>
    </div>
  </section>

  <section class="scholars-proof" aria-label="Exam Scholars highlights">
    <div class="site-container scholars-proof__grid">
      <div><strong>Thousands</strong><span>of past questions</span></div>
      <div><strong>Online or offline</strong><span>practise wherever you are</span></div>
      <div><strong>Multiple exams</strong><span>one practice companion</span></div>
    </div>
  </section>

  <section class="section section--white">
    <div class="site-container">
      <div class="section-heading scholars-section-heading">
        <p class="eyebrow">Built for better preparation</p>
        <h2>Every practice session can move you forward.</h2>
        <p>Build familiarity with exam formats, understand your results and focus your study time where it matters most.</p>
      </div>

      <div class="scholars-feature-grid">
        <article class="scholars-feature-card"><span class="scholars-feature-card__icon" aria-hidden="true">01</span><h3>Study or exam mode</h3><p>Learn at your own pace in Study mode, or practise under timed conditions in Exam mode.</p></article>
        <article class="scholars-feature-card"><span class="scholars-feature-card__icon" aria-hidden="true">02</span><h3>Understand every result</h3><p>Review your answers with explanations and see details such as time spent, speed and questions attempted.</p></article>
        <article class="scholars-feature-card"><span class="scholars-feature-card__icon" aria-hidden="true">03</span><h3>Track your progress</h3><p>Use performance insights to spot strengths, find topics to revisit and plan your next practice session.</p></article>
        <article class="scholars-feature-card"><span class="scholars-feature-card__icon" aria-hidden="true">04</span><h3>Practise without the internet</h3><p>Continue learning offline, so your preparation can fit around your schedule and connection.</p></article>
        <article class="scholars-feature-card"><span class="scholars-feature-card__icon" aria-hidden="true">05</span><h3>Get comfortable with CBT</h3><p>Practise in an exam-style interface designed to help learners get familiar with computer-based tests.</p></article>
        <article class="scholars-feature-card scholars-feature-card--accent"><span class="scholars-feature-card__icon" aria-hidden="true">06</span><h3>Prepare for more than one exam</h3><p>Find practice for JAMB/UTME, WAEC/SSCE, BECE, NCEE, Post-UTME and other exams.</p></article>
      </div>
    </div>
  </section>

  <section class="scholars-steps-section">
    <div class="site-container scholars-steps__grid">
      <div>
        <p class="eyebrow">Start practising</p>
        <h2>A simple routine for stronger exam readiness.</h2>
        <p>Choose an exam and subjects, practise questions, then use your results to guide what you study next.</p>
        <a class="btn btn--primary btn--large" href="https://examscholars.com" target="_blank" rel="noopener noreferrer">Go to Exam Scholars <span aria-hidden="true">↗</span></a>
      </div>
      <ol class="scholars-steps">
        <li><span>1</span><div><strong>Choose your exam</strong><p>Select the exam and subjects you want to prepare for.</p></div></li>
        <li><span>2</span><div><strong>Practise regularly</strong><p>Work through questions in Study mode or simulate exam conditions.</p></div></li>
        <li><span>3</span><div><strong>Review and improve</strong><p>Check explanations and performance insights to guide your next session.</p></div></li>
      </ol>
    </div>
  </section>

  <section class="scholars-final-cta">
    <div class="site-container scholars-final-cta__inner">
      <p class="eyebrow">Your next step starts here</p>
      <h2>Make your practice count.</h2>
      <p>Visit Exam Scholars to explore the practice tools and choose the right way to prepare.</p>
      <a class="btn btn--primary btn--large" href="https://examscholars.com" target="_blank" rel="noopener noreferrer">Visit examscholars.com <span aria-hidden="true">↗</span></a>
    </div>
  </section>
@endsection
