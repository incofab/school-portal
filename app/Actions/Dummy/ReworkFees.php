<?php
namespace App\Actions\Dummy;

use App\Enums\PaymentInterval;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Receipt;
use App\Support\MorphMap;
use DB;

class ReworkFees
{
  private $filename = '';
  function __construct()
  {
    $this->filename = public_path('fees.json');
  }

  static function make()
  {
    return new self();
  }

  function isBackedUp(): bool
  {
    return file_exists($this->filename);
  }

  function saveFeesDataToFile()
  {
    $data = self::getFeesData();
    // if(!file_exists($this->filename)){
    //   mk
    // }
    file_put_contents($this->filename, json_encode($data, JSON_PRETTY_PRINT));
    return successRes('Done');
  }

  function insertData()
  {
    $data = file_get_contents($this->filename);
    $data = json_decode($data);
    foreach ($data as $key => $fee) {
      $createdFee = Fee::query()->create([
        'institution_id' => $fee['institution_id'],
        'title' => $fee['title'],
        'amount' => $fee['amount'],
        'payment_interval' => $fee['payment_interval'],
        'term' => $fee['term'],
        'academic_session_id' => $fee['academic_session_id'],
        'fee_items' => $fee['fee_items']
      ]);
      $this->saveCategories($createdFee, $fee['fee_categories'] ?? []);
      $this->savePayments($createdFee, $fee['fee_payments'] ?? []);
    }
  }

  private function saveCategories($fee, array $categories)
  {
    foreach ($categories as $key => $category) {
      DB::table('fee_categories')->insert([...$category, 'fee_id' => $fee->id]);
    }
  }

  private function savePayments($fee, array $feePayments)
  {
    foreach ($feePayments as $key => $feePayment) {
      FeePayment::query()->create([
        'institution_id' => $fee->institution_id,
        'fee_id' => $fee->id,
        'amount' => $feePayment['amount'],
        'confirmed_by_user_id' => $feePayment['confirmed_by_user_id'],
        'channel' => $feePayment['channel'],
        'reference' => $feePayment['reference']
      ]);
    }
  }

  private function getFeeCategory(?Fee $fee)
  {
    if (!$fee) {
      return [];
    }
    if ($fee->classification_group_id) {
      return [
        'institution_id' => $fee->institution_id,
        'feeable_type' => MorphMap::key(ClassificationGroup::class),
        'feeable_id' => $fee->classification_group_id
      ];
    }
    if ($fee->classification_id) {
      return [
        'institution_id' => $fee->institution_id,
        'feeable_type' => MorphMap::key(Classification::class),
        'feeable_id' => $fee->classification_id
      ];
    }
    return [];
  }

  function getFeesData()
  {
    $data = [];
    $receiptTypes = DB::table('receipt_types')
      // ->with('fees.feePayments')
      ->latest('institution_id')
      ->get();
    foreach ($receiptTypes as $key => $receiptType) {
      $fees = Fee::query()
        ->where('receipt_type_id', $receiptType->id)
        ->with('feePayments')
        ->get();
      $amount = array_sum($fees->map(fn($item) => $item->amount)->toArray());
      $feeData = [
        'institution_id' => $receiptType->institution_id,
        'title' => $receiptType->title,
        'amount' => $amount,
        'payment_interval' =>
          $fees[0]->payment_interval ?? PaymentInterval::Termly->value,
        'term' => $fees->first()?->feePayments?->first()?->term ?? null,
        'academic_session_id' =>
          $fees->first()?->feePayments?->first()?->academic_session_id ?? null,
        'fee_categories' => [self::getFeeCategory($fees->first())]
      ];
      $feeItems = [];
      $feePayments = [];
      foreach ($fees as $key => $fee) {
        $feeItems[] = ['title' => $fee->title, 'amount' => $fee->amount];
        foreach ($fee->feePayments as $key => $feePayment) {
          $f = [
            'amount' => $feePayment->amount_paid,
            'confirmed_by_user_id' => $feePayment->confirmed_by_user_id,
            'channel' => $feePayment->method,
            'reference' => $feePayment->reference
          ];
          $feePayments[] = $f;
        }
      }
      $feeData['fee_items'] = $feeItems;
      $feeData['fee_payments'] = $feePayments;
      $data[] = $feeData;
    }
    return $data;
  }

  function downloadPayments()
  {
    $receipts = \App\Models\Receipt::query()
      ->with(
        'receiptType',
        'user',
        'feePayments.fee',
        'academicSession',
        'classification'
      )
      ->oldest('institution_id')
      ->get();
    $formatted = [];
    foreach ($receipts as $key => $receipt) {
      $formatted[] = [
        'Institution' => $receipt->institution->name,
        'ReceiptType' => $receipt->receiptType->title,
        'Student' => $receipt->user->full_name,
        'Amount' => $receipt->total_amount,
        'Term' => $receipt->term->value,
        'Academic Session' => $receipt->academicSession?->title,
        'Class' => $receipt->classification?->title,
        'Reference' => $receipt->reference,
        'Instalments' => $receipt->feePayments
          ?->map(
            fn($payment) => "{$payment->fee?->title} = {$payment->amount_paid}"
          )
          ->join(', ')
      ];
    }
    return (new \App\Actions\GenericExport(
      $formatted,
      'receipts.xlsx'
    ))->download();
  }
}
