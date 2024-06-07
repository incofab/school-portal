<?php
namespace App\Actions\Payments;

use App\Actions\RecordFeePayment;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Receipt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentStat
{
  /**
   * @param Institution $institution
   * @param ReceiptType $receiptType
   * @param ?AcademicSession $academicSession
   * @param ?string|null $term
   */
  function __construct(
    private Institution $institution,
    private ReceiptType $receiptType,
    private $academicSession,
    private $term
  ) {
  }

  public function upload(Builder $query)
  {
    $numOfPayments = (clone $query)->count('id');
    $totalAmountPaid = (clone $query)->count('amount');
  }
}
