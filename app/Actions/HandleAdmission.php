<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Institution;
use Illuminate\Support\Str;
use App\Actions\RecordStudent;
use App\Actions\RecordGuardian;
use App\Mail\AdmissionLetterMail;
use Illuminate\Support\Facades\DB;
use App\Models\AdmissionApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\AdmissionApplicationRequest;

class HandleAdmission
{
    private Institution $institution;

    public function __construct()
    {
        $this->institution = currentInstitution();
    }

    public static function make()
    {
        return new self();
    }

    public function admitStudent(AdmissionApplication $admissionApplication, $guardians, $data)
    {
        $sourcePath = $admissionApplication->photo;

        $parts = explode('/', $sourcePath);
        $fileName = end($parts);
        $destinationPath = 'avatars/users/' . $fileName;
        $destinationUrl = $parts[0] . '//' . $parts[2] . '/' . env('AWS_BUCKET') . '/avatars/users/' . $fileName;

        //== Use the Storage facade to put the image in the S3 bucket
        Storage::disk('s3_public')->put(
            $destinationPath,
            file_get_contents($sourcePath)
        );

        DB::beginTransaction();
        $student = RecordStudent::make([
            'classification_id' => $data['classification'],
            'email' => Str::orderedUuid() . '@email.com',
            'phone' => $guardians[0]["phone"],
            'guardian_phone' => $guardians[0]["phone"],
            'photo' => $destinationUrl,
            ...collect($admissionApplication)
                ->only(
                    'first_name',
                    'last_name',
                    'other_names',
                    'gender',
                    'photo',
                    'dob',
                )
        ])->create();

        foreach ($guardians as $guardian) {
            if (!User::whereEmail($guardian['email'])->exists()) {
                RecordGuardian::make([...collect($guardian)->except('id', 'admission_application_id')])->create($student->id);
            }
        }
        DB::commit();

        $dUrl = route('institutions.admissions.letter', [
            'institution' => $this->institution->uuid,
            'student' => $student->id,
        ]);

        //Mail::to($admissionApplication->fathers_email)->queue(new AdmissionLetterMail(User::first(), $dUrl));
    }
}
