<?php
namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\Institution;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
  // function create()
// {
//   return inertia('institutions/classifications/create-edit-classification');
// }
// function store()
// {
//   $data = request()->validate([
//     'title' => [
//       'required',
//       'string',
//       'max:100',
//       Rule::unique('classifications', 'title')->where(
//         'institution_id',
//         currentInstitution()->id
//       )
//     ],
//     'description' => ['nullable', 'string']
//   ]);
//   currentInstitution()
//     ->classifications()
//     ->create($data);
//   return $this->ok();
// }
// function edit(Institution $institution, Classification $classification)
// {
//   return inertia('institutions/classifications/create-edit-classification', [
//     'classification' => $classification
//   ]);
// }
// function update(Institution $institution, Classification $classification)
// {
//   $data = request()->validate([
//     'title' => [
//       'required',
//       'string',
//       'max:100',
//       Rule::unique('classifications', 'title')
//         ->where('institution_id', $institution->id)
//         ->ignore($classification->id, 'id')
//     ],
//     'description' => ['nullable', 'string']
//   ]);
//   $classification->fill($data)->save();
//   return $this->ok();
// }
// function destroy(Institution $institution, Classification $classification)
// {
//   $classification->delete();
//   return $this->ok();
// }
}
