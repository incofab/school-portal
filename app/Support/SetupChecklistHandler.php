<?php

namespace App\Support;

use App\Models\Institution;

class SetupChecklistHandler
{
  private static ?self $instance = null;
  private function __construct(private Institution $institution)
  {
    $institution->loadCount(
      'classifications',
      'courses',
      'students',
      'teachers',
      'courseTeachers',
      'fees'
    );
  }

  static function make(Institution $institution): self
  {
    if (!self::$instance) {
      self::$instance = new self($institution);
    }
    return self::$instance;
  }

  function getChecklist()
  {
    $todos = [];

    //= Add Classes
    $todos[] = [
      'count' => $this->institution->classifications_count,
      'route' => 'classifications.create',
      'label' => 'Add Classes',
      'required' => true
    ];

    //=  Add Subjects/Courses
    $todos[] = [
      'count' => $this->institution->courses_count,
      'route' => 'courses.create',
      'label' => 'Add Subjects',
      'required' => true
    ];

    //= Add Students
    $todos[] = [
      'count' => $this->institution->students_count,
      'route' => 'students.create',
      'label' => 'Add Students',
      'required' => true
    ];

    //= Add Teachers and Staff
    $todos[] = [
      'count' => $this->institution->teachers_count,
      'route' => 'users.create',
      'label' => 'Add Teachers / Staff',
      'required' => true
    ];

    //= Assign Teachers to subjects /CourseTeachers
    $todos[] = [
      'count' => $this->institution->course_teachers_count,
      'route' => 'course-teachers.create',
      'label' => 'Assign teachers to subjects',
      'required' => true
    ];

    //= Set up Fees
    $todos[] = [
      'count' => $this->institution->fees_count,
      'route' => 'fees.create',
      'label' => 'Add Fees',
      'required' => false
    ];

    return $todos;
  }

  function isSetupComplete(): bool
  {
    $todos = $this->getChecklist();
    $isSetupComplete = true;
    foreach ($todos as $todo) {
      if ($todo['count'] == 0) {
        $isSetupComplete = false;
        break;
      }
    }
    return $isSetupComplete;
  }
}
