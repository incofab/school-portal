<?php
namespace App\Actions;

use App\Enums\Grade;
use App\Models\CourseResult;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\ReceiptType;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

class SaveSentMessages
{
  function __construct(
    private ReceiptType $receiptType,
    private Student $student
  ) {
  }

  function run()
  {
    return $this->saveToDatabase();
  }

  private function saveToDatabase()
  {

  }
}
