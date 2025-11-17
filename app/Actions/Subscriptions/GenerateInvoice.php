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

  function __construct(
    private InstitutionGroup $institutionGroup,
    private AcademicSession $academicSession,
    private TermType $term
  ) {
    $this->run();
  }

  private function run()
  {
    $priceList = $this->institutionGroup
      ->priceLists()
      ->where('type', PriceType::ResultChecking)
      ->first();

    if (!$priceList) {
      abort(
        404,
        'Price list for result checking not found for this institution group.'
      );
    }

    $invoiceItems = [];
    $totalAmount = 0;

    $institutions = $this->institutionGroup->institutions;

    foreach ($institutions as $inst) {
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
          'payment_structure' => $priceList->payment_structure
        ])
        ->first();

      if ($isTermly) {
        $studentCount = $inst->students()->count();
        $amount *= $studentCount;
        $quantity = $studentCount;
      }

      // if ($publication || $this->institutionGroup->credit_wallet >= $amount) {
      //   continue; // This institution must have paid since they've published results or they have enough balance
      // }

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

    abort_if(
      empty($invoiceItems),
      404,
      'There are no pending invoice for this institution'
    );

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
