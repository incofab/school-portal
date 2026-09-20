<?php

use App\DTO\AI\AssistantActorContext;
use App\Models\Faq;
use App\Services\AI\Knowledge\DatabaseKnowledgeRetriever;

beforeEach(function () {
    $this->retriever = app(DatabaseKnowledgeRetriever::class);
});

it('retrieves the expected FAQ across representative assistant workflows', function (
    string $name,
    string $description,
    string $query
) {
    $faq = Faq::factory()->create([
        'name' => $name,
        'description' => "<p>{$description}</p>",
    ]);
    $result = $this->retriever->search(
        $query,
        new AssistantActorContext(null, null, null, true)
    );

    expect($result->toArray()[0]['id'])->toBe("faq:{$faq->id}");
})->with([
    [
        'How do I mark a learner present?',
        'Open Attendance, choose the class and date, then mark each learner present or absent.',
        'Where do I record attendance for a learner?',
    ],
    [
        'How do I settle an outstanding fee?',
        'Open Fees, choose the outstanding fee and pay with a card or bank transfer.',
        'How can I pay the school fee balance online?',
    ],
    [
        'How do I publish processed results?',
        'Open Class Result Analysis, confirm the session and term, then publish the processed result.',
        'Where can I publish a processed class result?',
    ],
    [
        'How do I view a class performance summary?',
        'Open the class performance report to compare subject scores and class averages.',
        'I need the class average performance report.',
    ],
    [
        'How do I add a teacher to a subject?',
        'Open Teacher Assignments, choose the subject and teacher, then save the assignment.',
        'Where can I assign a teacher to a subject?',
    ],
]);
