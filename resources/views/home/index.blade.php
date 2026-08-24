@extends('layouts.website')

@section('title', 'EduManager - Complete School Management Software for Modern Schools')
@section('meta_description', 'EduManager helps schools manage students, admissions, attendance, fees, results, online exams, parent communication, reports, and daily school automation from one secure digital platform.')
@section('meta_keywords', 'EduManager, school management software, school automation, student management system, admission management, attendance tracking, school fees management, result management system, digital school platform')

@php
  $featureCards = [
    [
      'icon' => 'ST',
      'title' => 'Student Management',
      'description' => 'Keep accurate student profiles, class history, guardians, documents, IDs, transcripts, and academic records in one organized student management system.',
    ],
    [
      'icon' => 'AD',
      'title' => 'Admission Management',
      'description' => 'Run digital admission forms, application review, form sales, applicant records, admission letters, and recruitment workflows without paper-heavy processes.',
    ],
    [
      'icon' => 'AT',
      'title' => 'Attendance Tracking',
      'description' => 'Record daily attendance, monitor check-in and check-out status, view class registers, and identify attendance patterns quickly.',
    ],
    [
      'icon' => 'FE',
      'title' => 'Fees & Payments',
      'description' => 'Manage fees, invoices, receipts, manual payment reviews, payment notifications, funding wallets, and financial records with better visibility.',
    ],
    [
      'icon' => 'RS',
      'title' => 'Results & Report Cards',
      'description' => 'Record scores, compute grades and positions, publish report cards, generate transcripts, and share result access securely with parents.',
    ],
    [
      'icon' => 'CM',
      'title' => 'Parent Communication',
      'description' => 'Reach parents and guardians through messages, notifications, WhatsApp-enabled communication, and student-dependent access.',
    ],
    [
      'icon' => 'TC',
      'title' => 'Teacher Management',
      'description' => 'Coordinate teachers, subject assignments, class responsibilities, comments, lesson plans, lesson notes, and academic workflows.',
    ],
    [
      'icon' => 'CL',
      'title' => 'Classes & Subjects',
      'description' => 'Manage classes, arms, classification groups, subjects, course teachers, timetables, activities, and academic-session structures.',
    ],
    [
      'icon' => 'EX',
      'title' => 'Online Exams & CBT',
      'description' => 'Create exams, map questions, run computer-based tests, support public exam result lookups, and manage assessments from one platform.',
    ],
    [
      'icon' => 'AU',
      'title' => 'School Automation',
      'description' => 'Automate repetitive administrative work across curriculum, payroll, library, live classes, assignments, notifications, and school operations.',
    ],
    [
      'icon' => 'AN',
      'title' => 'Reports & Analytics',
      'description' => 'Use structured reports for attendance, results, payments, assessments, subjects, classes, and operational decisions.',
    ],
    [
      'icon' => 'SC',
      'title' => 'Multi-role Access',
      'description' => 'Give administrators, teachers, students, guardians, accountants, partners, and managers the right access for their responsibilities.',
    ],
  ];

  $reasons = [
    'Saves hours of administrative time every week',
    'Reduces paperwork across admissions, fees, attendance, and results',
    'Improves communication between school, teachers, students, and parents',
    'Helps schools operate efficiently with structured digital workflows',
    'Provides a complete school management software foundation for growth',
  ];

  $stats = [
    ['value' => '360°', 'label' => 'School operations covered'],
    ['value' => '24/7', 'label' => 'Digital access for stakeholders'],
    ['value' => '1', 'label' => 'Connected platform for every school team'],
    ['value' => '100%', 'label' => 'Built for Nigerian school workflows'],
  ];

  $testimonials = [
    [
      'quote' => 'EduManager has helped us organize school records, results, attendance, and communication in a way our staff can actually use every day.',
      'name' => 'Ademoyega Kudirat',
      'role' => 'Principal, Greater Heights Secondary School',
    ],
    [
      'quote' => 'The platform gives our administrators, teachers, and parents a clearer view of what is happening across the school.',
      'name' => 'Alice Johnson',
      'role' => 'Head Teacher, Later Rain Primary School',
    ],
    [
      'quote' => 'Managing admissions, payments, and reports is easier because the important school data now lives in one reliable place.',
      'name' => 'Tobi Brown',
      'role' => 'Administrator, Global Scholars International School',
    ],
  ];
@endphp

@section('content')
  <section class="hero-section">
    <div class="site-container hero-section__grid">
      <div class="hero-section__content">
        <p class="eyebrow">Complete school management software</p>
        <h1>Run your school with clarity, speed, and confidence.</h1>
        <p class="hero-section__lead">
          EduManager brings student management, admissions, attendance, fees, results, online exams, parent communication, and school automation into one secure digital school platform.
        </p>
        <div class="hero-section__actions" aria-label="Primary calls to action">
          <a class="btn btn--primary btn--large" href="{{ route('registration-requests.create') }}">Get Started</a>
          <a class="btn btn--secondary btn--large" href="mailto:support@edumanager.ng?subject=EduManager%20Demo%20Request">Request Demo</a>
          <a class="btn btn--text" href="{{ route('login') }}">Login to your school</a>
        </div>
        <dl class="hero-section__proof" aria-label="EduManager platform highlights">
          <div>
            <dt>Admissions</dt>
            <dd>From form sales to letters</dd>
          </div>
          <div>
            <dt>Results</dt>
            <dd>Scores, report cards, transcripts</dd>
          </div>
          <div>
            <dt>Payments</dt>
            <dd>Fees, receipts, notifications</dd>
          </div>
        </dl>
      </div>

      <div class="hero-stage" aria-label="EduManager school operations dashboard preview">
        <div class="hero-stage__glow" aria-hidden="true"></div>
        <div class="hero-visual">
          <div class="hero-visual__topbar">
            <span></span>
            <span></span>
            <span></span>
          </div>
          <div class="hero-visual__body">
            <div class="hero-visual__summary">
              <p>Today&apos;s Operations</p>
              <strong>School command center</strong>
              <span>Live data across admissions, fees, attendance, results, and parent communication.</span>
            </div>
            <img
              class="hero-visual__image"
              src="{{ asset('img/website-hero-dashboard.svg') }}"
              alt="EduManager dashboard showing school management widgets"
            />
          </div>
          <div class="hero-visual__metrics">
            <article>
              <span>Attendance</span>
              <strong>94%</strong>
            </article>
            <article>
              <span>Fees tracked</span>
              <strong>NGN 8.7m</strong>
            </article>
            <article>
              <span>Results ready</span>
              <strong>312</strong>
            </article>
          </div>
        </div>

        <div class="hero-floating-card hero-floating-card--top">
          <span class="status-dot status-dot--green"></span>
          <strong>New admissions reviewed</strong>
          <small>28 applications</small>
        </div>
        <div class="hero-floating-card hero-floating-card--bottom">
          <span class="status-dot status-dot--orange"></span>
          <strong>Report cards queued</strong>
          <small>Ready for guardian access</small>
        </div>
      </div>
    </div>
  </section>

  <section class="logo-strip" aria-label="EduManager value summary">
    <div class="site-container logo-strip__inner">
      <span>Trusted platform for school owners</span>
      <span>Built for administrators</span>
      <span>Useful for teachers</span>
      <span>Clear for parents</span>
      <span>Accessible to students</span>
    </div>
  </section>

  <section id="features" class="section section--white">
    <div class="site-container">
      <div class="section-heading">
        <p class="eyebrow">Platform features</p>
        <h2>Everything a modern school needs to operate digitally.</h2>
        <p>
          EduManager is more than a student records tool. It connects the academic, administrative, financial, and communication workflows that keep a school moving.
        </p>
      </div>

      <div class="feature-grid">
        @foreach ($featureCards as $feature)
          <article class="feature-card" style="--feature-delay: {{ $loop->index * 70 }}ms">
            <div class="feature-card__icon" aria-hidden="true">{{ $feature['icon'] }}</div>
            <h3>{{ $feature['title'] }}</h3>
            <p>{{ $feature['description'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section id="why-edumanager" class="section section--tinted">
    <div class="site-container split-section">
      <div class="split-section__content">
        <p class="eyebrow">Why schools choose EduManager</p>
        <h2>Less manual work. Better records. More time for education.</h2>
        <p>
          Schools choose EduManager because it gives their teams a complete digital school management solution without scattering daily work across notebooks, spreadsheets, messaging apps, and disconnected files.
        </p>
        <ul class="check-list">
          @foreach ($reasons as $reason)
            <li>{{ $reason }}</li>
          @endforeach
        </ul>
      </div>
      <div class="workflow-card">
        <h3>From manual process to connected workflow</h3>
        <div class="workflow-card__steps">
          <div>
            <span>01</span>
            <strong>Capture</strong>
            <p>Register students, staff, classes, subjects, admissions, and fee structures.</p>
          </div>
          <div>
            <span>02</span>
            <strong>Operate</strong>
            <p>Run attendance, assignments, exams, payments, messages, lesson planning, and reports.</p>
          </div>
          <div>
            <span>03</span>
            <strong>Improve</strong>
            <p>Use analytics, report cards, transcripts, and financial summaries to make better decisions.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="trust" class="section section--dark">
    <div class="site-container">
      <div class="stats-grid">
        @foreach ($stats as $stat)
          <article>
            <strong>{{ $stat['value'] }}</strong>
            <span>{{ $stat['label'] }}</span>
          </article>
        @endforeach
      </div>

      <div class="trust-grid">
        <article>
          <p class="eyebrow">Security & reliability</p>
          <h2>Built to protect sensitive school information.</h2>
          <p>
            EduManager supports role-based access, structured school data, institution-aware workflows, payment records, audit-friendly operations, and secure stakeholder access. Your school can digitize confidently while keeping the right information in the right hands.
          </p>
        </article>
        <article class="support-card" id="support">
          <h3>Responsive support when your team needs help</h3>
          <p>
            Our support team helps schools onboard, configure important workflows, and resolve questions as administrators, teachers, accountants, parents, and students use the platform.
          </p>
          <a class="btn btn--secondary" href="mailto:support@edumanager.ng">Contact Support</a>
        </article>
      </div>
    </div>
  </section>

  <section class="section section--white">
    <div class="site-container">
      <div class="section-heading">
        <p class="eyebrow">School feedback</p>
        <h2>Designed for real school teams and daily operational pressure.</h2>
      </div>

      <div class="testimonial-grid">
        @foreach ($testimonials as $testimonial)
          <article class="testimonial-card">
            <p>&ldquo;{{ $testimonial['quote'] }}&rdquo;</p>
            <div>
              <strong>{{ $testimonial['name'] }}</strong>
              <span>{{ $testimonial['role'] }}</span>
            </div>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="final-cta">
    <div class="site-container final-cta__inner">
      <p class="eyebrow">Start your digital transformation</p>
      <h2>Give your school a complete management platform built for growth.</h2>
      <p>
        Join schools using EduManager to reduce paperwork, organize records, improve communication, track payments, publish results, and operate with more confidence.
      </p>
      <div class="final-cta__actions">
        <a class="btn btn--primary btn--large" href="{{ route('registration-requests.create') }}">Subscribe to EduManager</a>
        <a class="btn btn--secondary btn--large" href="mailto:support@edumanager.ng?subject=EduManager%20Information%20Request">Request More Information</a>
      </div>
    </div>
  </section>
@endsection
