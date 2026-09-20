<?php

use App\Http\Controllers\AI\AssistantController;
use App\Http\Controllers\Managers\AI\AssistantDiagnosticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI Assistant Routes
|--------------------------------------------------------------------------
|
| All assistant endpoints live here. Each surface declares its complete
| middleware stack because this file is loaded independently from the
| general web, institution, and manager route files.
|
*/

Route::middleware('web')
    ->prefix('assistant')
    ->name('assistant.')
    ->group(function () {
        Route::get('/', [AssistantController::class, 'index'])
            ->name('index');
        Route::post('/conversations', [AssistantController::class, 'storeConversation'])
            ->middleware('throttle:ai-assistant')
            ->name('conversations.store');
        Route::get('/conversations/{aiConversation}', [AssistantController::class, 'show'])
            ->name('conversations.show');
        Route::patch('/conversations/{aiConversation}', [AssistantController::class, 'updateConversation'])
            ->name('conversations.update');
        Route::post('/conversations/{aiConversation}/messages', [AssistantController::class, 'sendMessage'])
            ->middleware('throttle:ai-assistant')
            ->name('messages.store');
        Route::post('/conversations/{aiConversation}/messages/{message}/feedback', [AssistantController::class, 'storeFeedback'])
            ->name('messages.feedback');
        Route::delete('/conversations/{aiConversation}', [AssistantController::class, 'destroy'])
            ->name('conversations.destroy');
        Route::delete('/conversations/{aiConversation}/forget', [AssistantController::class, 'forget'])
            ->name('conversations.forget');
    });

Route::middleware(['web', 'auth', 'manager'])
    ->prefix('manager/ai-assistant')
    ->name('managers.ai-assistant.')
    ->group(function () {
        Route::get('/diagnostics', AssistantDiagnosticsController::class)
            ->name('diagnostics');
    });

Route::middleware(['web', 'auth', 'institution.user'])
    ->prefix('{institution}/assistant')
    ->name('institutions.assistant.')
    ->group(function () {
        Route::get('/', [AssistantController::class, 'index'])
            ->name('index');
        Route::post('/conversations', [AssistantController::class, 'storeConversation'])
            ->middleware('throttle:ai-assistant')
            ->name('conversations.store');
        Route::get('/conversations/{aiConversation}', [AssistantController::class, 'showForInstitution'])
            ->name('conversations.show');
        Route::patch('/conversations/{aiConversation}', [AssistantController::class, 'updateConversationForInstitution'])
            ->name('conversations.update');
        Route::post('/conversations/{aiConversation}/messages', [AssistantController::class, 'sendMessageForInstitution'])
            ->middleware('throttle:ai-assistant')
            ->name('messages.store');
        Route::post('/conversations/{aiConversation}/actions/{execution}/confirm', [AssistantController::class, 'confirmActionForInstitution'])
            ->middleware('throttle:ai-assistant')
            ->name('actions.confirm');
        Route::post('/conversations/{aiConversation}/actions/{execution}/cancel', [AssistantController::class, 'cancelActionForInstitution'])
            ->name('actions.cancel');
        Route::post('/conversations/{aiConversation}/messages/{message}/feedback', [AssistantController::class, 'storeFeedbackForInstitution'])
            ->name('messages.feedback');
        Route::delete('/conversations/{aiConversation}', [AssistantController::class, 'destroyForInstitution'])
            ->name('conversations.destroy');
        Route::delete('/conversations/{aiConversation}/forget', [AssistantController::class, 'forgetForInstitution'])
            ->name('conversations.forget');
    });
