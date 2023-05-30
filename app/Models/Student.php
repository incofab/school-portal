<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
  use HasFactory, SoftDeletes;

  public $guarded = [];

  // static function multiInsert($post, Institution $institution)
  // {
  //   foreach ($post as $arr) {
  //     $arr['code'] = Student::generateStudentID();
  //     $arr['institution_id'] = $institution->id;

  //     Student::create($arr);
  //   }

  //   return retS('All records inserted');
  // }

  // static function insert($post, Institution $institution)
  // {
  //   $post['code'] = Student::generateStudentID();
  //   $post['institution_id'] = $institution->id;

  //   $data = Student::create($post);

  //   if (!$data) {
  //     return retF('Error: Data entry failed');
  //   }

  //   $msg = 'Registration successful, You can login now';
  //   return retS($msg, $data);
  // }

  // static function edit($post)
  // {
  //   $student = Student::whereId($post['id'])->firstOrFail();

  //   $student->update($post);

  //   return retS('Record updated successfully', $student);
  // }

  static function generateStudentID()
  {
    $prefix = date('Y') . '-';

    $key = $prefix . rand(1000000, 9999999);

    while (Student::where('code', '=', $key)->first()) {
      $key = $prefix . rand(1000000, 9999999);
    }

    return $key;
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
