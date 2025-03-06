<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Institutions as Web;

//Institution
Route::get('dummy', function() {
    dd('dmoddsdsd');
})->name('dummy');

Route::get('/students/search', Web\Students\SearchStudentController::class)->name('students.search');

Route::get('/receipts/{receipt:reference}/show', [Web\Students\StudentFeePaymentController::class, 'showReceipt'])->name('receipts.show');

Route::get('/receipt-type-fees/{student}/{classification}/{term}/{academicSession}/show', [Web\Students\StudentFeePaymentController::class, 'showReceiptTypeFee'])->name('receipt-type-fees.show');

Route::prefix('students/{student}')->name('students.')->middleware(['student.access'])->group(function () {
    Route::get('term-result-detail/{classification}/{academicSession}/{term}/{forMidTerm}', Web\Students\StudentTermResultDetailController::class)
        ->name('term-result-detail');
    Route::get('/result-sheet/{classification}/{academicSession}/{term}/{forMidTerm}', [Web\Students\ViewResultSheetController::class, 'viewResult'])
        ->name('result-sheet');

    Route::get('transcript', Web\Students\ShowTranscriptController::class)->name('transcript');
    
    Route::get('term-results', Web\Students\ListStudentTermResultController::class)
        ->name('term-results.index');

    Route::get('receipts', [Web\Students\StudentFeePaymentController::class, 'receipts'])->name('receipts.index');
    
    Route::get('fee-payments/create', [Web\Students\StudentFeePaymentController::class, 'feePaymentView'])->name('fee-payments.create');
    Route::post('fee-payments/store', [Web\Students\StudentFeePaymentController::class, 'feePaymentStore'])->name('fee-payments.store');
    Route::get('fee-payments/{receipt}', [Web\Students\StudentFeePaymentController::class, 'index'])->name('fee-payments.index');
});
    
    
Route::get('/session-results/index', [Web\Students\SessionResultController::class, 'index'])
    ->name('session-results.index');
Route::get('/session-results/{sessionResult}', [Web\Students\SessionResultController::class, 'show'])
    ->name('session-results.show');
Route::delete('/session-results/{sessionResult}', [Web\Students\SessionResultController::class, 'destroy'])
    ->name('session-results.destroy');

