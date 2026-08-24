<?php

use Tests\TestCase;

uses(TestCase::class);

it(
  'renders the marketing homepage with reusable website structure',
  function () {
    $html = view('home.index')->render();

    expect($html)
      ->toContain(
        'EduManager - Complete School Management Software for Modern Schools'
      )
      ->toContain('Run your school with clarity, speed, and confidence.')
      ->toContain('Student Management')
      ->toContain('Admission Management')
      ->toContain('Attendance Tracking')
      ->toContain('Fees &amp; Payments')
      ->toContain('Results &amp; Report Cards')
      ->toContain('Parent Communication')
      ->toContain('Teacher Management')
      ->toContain('Online Exams &amp; CBT')
      ->toContain('Security & reliability')
      ->toContain('Subscribe to EduManager')
      ->toContain('css/website.css')
      ->toContain('img/logo.png')
      ->toContain('img/website-hero-dashboard.svg');
  }
);
