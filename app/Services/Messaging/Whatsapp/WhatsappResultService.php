<?php

namespace App\Services\Messaging\Whatsapp;

use App\Models\Student;
use App\Models\TermResult;
use App\Support\SettingsHandler;

class WhatsappResultService
{
  public function buildResultResponse(Student $student): WhatsappResultResponse
  {
    $student->loadMissing(
      'user',
      'classification',
      'institutionUser.institution.institutionSettings'
    );

    $institution = $student->institutionUser?->institution;
    if (!$institution) {
      return new WhatsappResultResponse(
        'We could not identify the school for this student. Please contact your school administrator.'
      );
    }

    $settings = SettingsHandler::makeFromInstitution($institution);
    $academicSessionId = $settings->getCurrentAcademicSession(null);
    $term = $settings->getCurrentTerm();
    $forMidTerm = $settings->isOnMidTerm();

    if (!$academicSessionId || !$term) {
      return new WhatsappResultResponse(
        'We could not find the current academic term/session for your school. Please contact your school.'
      );
    }

    $termResult = TermResult::query()
      ->where('institution_id', $institution->id)
      ->where('student_id', $student->id)
      ->where('academic_session_id', $academicSessionId)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm)
      ->with('institution', 'student.user', 'classification', 'academicSession')
      ->first();

    if (!$termResult) {
      return new WhatsappResultResponse(
        'We could not find a result for the current term/session yet.'
      );
    }

    if (!$termResult->isPublished()) {
      return new WhatsappResultResponse(
        'Your result is not yet published. Please check again later or contact your school.'
      );
    }

    if ($settings->resultActivationRequired() && !$termResult->isActivated()) {
      return new WhatsappResultResponse(
        'Your result is available, but result checking needs to be activated. Please contact your school or follow the school activation process.'
      );
    }

    return new WhatsappResultResponse(
      $this->availableMessage($termResult),
      true
    );
  }

  private function availableMessage(TermResult $termResult): string
  {
    $student = $termResult->student;
    $term = $termResult->term?->value ?? $termResult->term;
    $term = ucfirst(str_replace('-', ' ', (string) $term));

    return implode("\n", [
      'Your result is ready.',
      '',
      'Student: ' . ($student?->user?->full_name ?? 'Student'),
      'Class: ' . ($termResult->classification?->title ?? 'N/A'),
      'Term: ' . $term,
      'Session: ' . ($termResult->academicSession?->title ?? 'N/A'),
      'School: ' . ($termResult->institution?->name ?? 'N/A'),
      '',
      'Tap the link below to view your result:',
      $termResult->signedUrl()
    ]);
  }
}
