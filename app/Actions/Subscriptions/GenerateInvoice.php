<?php
namespace App\Actions\Subscriptions;

use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\InstitutionGroup;
use App\Models\ResultPublication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;

class GenerateInvoice
{
  private array $data;

  /**
   * @param array {title: string, amount: float|int }[] $extraItems
   */
  function __construct(
    private InstitutionGroup $institutionGroup,
    private AcademicSession $academicSession,
    private TermType $term,
    private array $extraItems = []
  ) {
    $this->run();
  }

  private function run()
  {
    $priceList = $this->institutionGroup
      ->priceLists()
      ->where('type', PriceType::ResultChecking)
      ->first();

    abort_unless(
      $priceList,
      404,
      'Price list for result checking not found for this institution group.'
    );

    $invoiceItems = [];
    $totalAmount = 0;

    $institutions = $this->institutionGroup->institutions;

    foreach ($institutions as $index => $inst) {
      $amount = $priceList->amount;
      $quantity = 1;
      $unitPrice = $amount;

      $isTermly = in_array($priceList->payment_structure, [
        PaymentStructure::PerStudentPerTerm,
        PaymentStructure::PerStudentPerSession
      ]);

      $publication = ResultPublication::query()
        ->where([
          'institution_group_id' => $this->institutionGroup->id,
          'academic_session_id' => $this->academicSession->id,
          'institution_id' => $inst->id,
          'payment_structure' => $priceList->payment_structure,
          ...$isTermly ? ['term' => $this->term] : []
        ])
        ->first();

      if ($publication) {
        continue; // This institution must have paid since they've published results
      }
      if (!$isTermly && $index > 0) {
        continue;
      }

      if ($isTermly) {
        $studentCount = $inst->students()->count();
        $amount *= $studentCount;
        $quantity = $studentCount;
      }

      $invoiceItems[] = [
        'institution' => $inst,
        'description' =>
          "Platform usage fee for {$this->academicSession->title}" .
          ($isTermly ? " ({$this->term->value} term)" : ''),
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'amount' => $amount
      ];

      $totalAmount += $amount;
    }

    // abort_if($this->institutionGroup->credit_wallet >= $totalAmount, 403, "This account have enought balance");

    foreach ($this->extraItems as $extraItem) {
      $invoiceItems[] = [
        'institution' => null,
        'description' => $extraItem['title'],
        'quantity' => 1,
        'unit_price' => $extraItem['amount'],
        'amount' => $extraItem['amount']
      ];

      $totalAmount += $extraItem['amount'];
    }

    abort_if(
      empty($invoiceItems),
      404,
      'There are no pending invoice for this institution'
    );

    $totalAmount += $this->institutionGroup->debt_wallet;

    $this->data = [
      'invoice_number' => uniqid('INV-'),
      'invoice_date' => now()->toFormattedDateString(),
      'institution_group' => $this->institutionGroup,
      'academic_session' => $this->academicSession,
      'term' => $this->term,
      'items' => $invoiceItems,
      'total_amount' => $totalAmount
    ];
  }

  function downloadAsPdf(): HttpResponse
  {
    $pdf = Pdf::loadView('invoices.institution-group-invoice', $this->data);
    return $pdf->download('invoice.pdf');
  }

  function viewAsHtml()
  {
    // dd($this->data);
    return view('invoices.institution-group-invoice', $this->data);
  }
}
