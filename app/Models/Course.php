<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
  use HasFactory, InstitutionScope;

  protected $fillable = [
    'code',
    'category',
    'title',
    'description',
    'is_file_content_uploaded',
    'institution_id'
  ];

  // static function insert($post)
  // {
  //     /** @var $val \Illuminate\Contracts\Validation\Validator|\Illuminate\Contracts\Validation\Factory */
  //     $val = \Illuminate\Support\Facades\Validator::make($post, self::ruleCreate());

  //     if ($val->fails())
  //     {
  //         return [SUCCESSFUL => false, MESSAGE => 'Validation failed: '.getFirstValue($val->errors()->toArray()), 'val' => $val ];
  //     }

  //     if(Course::where('', '=', $post[''])
  //         ->where('institution_id', '=', $post['institution_id'])->first()){
  //             return [SUCCESSFUL => false, MESSAGE => 'Error: Course code already exist'];
  //     }

  //     $created = static::create($post);

  //     if ($created)
  //     {
  //         return [SUCCESSFUL => true, MESSAGE => 'Data recorded successfully', 'data' => $created];
  //     }

  //     return [SUCCESSFUL => false, MESSAGE => 'Data recorded successfully'];
  // }

  // static function edit($post)
  // {
  //     /** @var $val \Illuminate\Contracts\Validation\Validator|\Illuminate\Contracts\Validation\Factory */
  //     $val = \Illuminate\Support\Facades\Validator::make($post, self::ruleUpdate());

  //     if ($val->fails())
  //     {
  //         return [SUCCESSFUL => false, MESSAGE => 'Validation failed: '.getFirstValue($val->errors()->toArray()), 'val' => $val ];
  //     }

  //     $old = Course::where('id', '=', $post['id'])->first();

  //     //Check if row exists
  //     if (!$old) return retF('There is no existing record, create new one');

  //     $success = self::where('id', '=', $post['id'])->update($post);

  //     if (!$success) return retF('Error: Update failed');

  //     return retF('Record updated successfully');
  // }

  public function canDelete()
  {
    return $this->sessions()
      ->get()
      ->count() === 0 &&
      $this->topics()
        ->get()
        ->count() === 0 &&
      $this->summaryChapters()
        ->get()
        ->count() === 0;
  }

  function institution()
  {
    return $this->belongsTo(
      \App\Models\Institution::class,
      'institution_id',
      'id'
    );
  }

  function sessions()
  {
    return $this->hasMany(\App\Models\CourseSession::class, 'course_id', 'id');
  }

  function topics()
  {
    return $this->hasMany(\App\Models\Topic::class, 'course_id', 'id');
  }

  function summaryChapters()
  {
    return $this->hasMany(\App\Models\Summary::class, 'course_id', 'id');
  }

  function courseTeachers()
  {
    return $this->hasMany(\App\Models\CourseTeacher::class);
  }
}
