<?php

use App\Enums\RecruitmentApplicationStatus;
use App\Models\Institution;
use App\Models\RecruitmentApplication;
use App\Models\User;
use App\Models\VacancyPost;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
});

it('lets institution admins create and list vacancy posts', function () {
    $route = route('institutions.vacancy-posts.store', [
        'institution' => $this->institution->uuid,
    ]);

    actingAs($this->admin)
        ->postJson($route, [])
        ->assertJsonValidationErrors(['title', 'employment_type', 'description']);

    actingAs($this->admin)
        ->postJson($route, [
            'title' => 'Mathematics Teacher',
            'department' => 'Academics',
            'employment_type' => 'full-time',
            'location' => 'Lagos',
            'summary' => 'Experienced mathematics teacher needed.',
            'description' => 'Teach mathematics and support academic reporting.',
            'requirements' => 'B.Ed or related qualification.',
            'responsibilities' => 'Prepare lessons and assess learners.',
            'positions_available' => 2,
            'is_published' => true,
        ])
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json) => $json
                ->has('vacancyPost')
                ->where('vacancyPost.title', 'Mathematics Teacher')
                ->etc()
        );

    assertDatabaseHas('vacancy_posts', [
        'institution_id' => $this->institution->id,
        'title' => 'Mathematics Teacher',
        'is_published' => true,
    ]);

    actingAs($this->admin)
        ->getJson(route('institutions.vacancy-posts.index', [
            'institution' => $this->institution->uuid,
        ]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $assert) => $assert
                ->component('institutions/recruitment/list-vacancy-posts')
                ->has('vacancyPosts.data', 1)
        );
});

it('exposes only published vacancies to the public', function () {
    VacancyPost::factory()
        ->for($this->institution)
        ->create(['title' => 'Published Vacancy', 'is_published' => true]);
    VacancyPost::factory()
        ->for($this->institution)
        ->create(['title' => 'Draft Vacancy', 'is_published' => false]);

    $route = route('institutions.recruitment.public-index', [
        'institution' => $this->institution->uuid,
    ]);

    $this->get($route)
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $assert) => $assert
                ->component('institutions/recruitment/public-vacancy-posts')
                ->has('vacancyPosts', 1)
                ->where('vacancyPosts.0.title', 'Published Vacancy')
        );
});

it('lets the public apply to a published vacancy with links only', function () {
    $vacancyPost = VacancyPost::factory()
        ->for($this->institution)
        ->create(['is_published' => true]);

    $route = route('institutions.recruitment-applications.store', [
        'institution' => $this->institution->uuid,
        'vacancyPost' => $vacancyPost->id,
    ]);

    postJson($route, [
        'vacancy_post_id' => $vacancyPost->id,
    ])->assertJsonValidationErrors([
        'reference',
        'first_name',
        'last_name',
        'email',
        'phone',
        'cover_letter',
    ]);

    postJson($route, [
        'vacancy_post_id' => $vacancyPost->id,
        'reference' => 'candidate-reference-1',
        'first_name' => 'Ada',
        'last_name' => 'Okafor',
        'email' => 'ada@example.test',
        'phone' => '08030000000',
        'current_role' => 'Teacher',
        'years_of_experience' => 4,
        'highest_qualification' => 'B.Ed',
        'cover_letter' => 'I am interested in this teaching role.',
        'cover_letter_url' => 'https://example.test/ada-cover-letter',
        'portfolio_url' => 'https://example.test/ada',
    ])->assertOk();

    postJson($route, [
        'vacancy_post_id' => $vacancyPost->id,
        'reference' => 'candidate-reference-1',
        'first_name' => 'Ada',
        'last_name' => 'Okafor',
        'email' => 'ada@example.test',
        'phone' => '08030000000',
        'cover_letter' => 'I am interested in this teaching role.',
    ])->assertJsonValidationErrorFor('reference');

    assertDatabaseCount('recruitment_applications', 1);
    assertDatabaseHas('recruitment_applications', [
        'institution_id' => $this->institution->id,
        'vacancy_post_id' => $vacancyPost->id,
        'first_name' => 'Ada',
        'last_name' => 'Okafor',
        'status' => RecruitmentApplicationStatus::Pending->value,
    ]);
});

it('does not accept applications to unpublished vacancies', function () {
    $vacancyPost = VacancyPost::factory()
        ->for($this->institution)
        ->create(['is_published' => false]);

    postJson(route('institutions.recruitment-applications.store', [
        'institution' => $this->institution->uuid,
        'vacancyPost' => $vacancyPost->id,
    ]), [
        'vacancy_post_id' => $vacancyPost->id,
        'reference' => 'candidate-reference-2',
        'first_name' => 'Ada',
        'last_name' => 'Okafor',
        'email' => 'ada@example.test',
        'phone' => '08030000000',
        'cover_letter' => 'I am interested in this teaching role.',
    ])->assertJsonValidationErrorFor('vacancy_post_id');
});

it('lets admins review recruitment applications', function () {
    $vacancyPost = VacancyPost::factory()
        ->for($this->institution)
        ->create();
    $application = RecruitmentApplication::factory()
        ->vacancyPost($vacancyPost)
        ->create();

    $ordinaryUser = User::factory()
        ->admin()
        ->create();

    actingAs($ordinaryUser)
        ->getJson(route('institutions.recruitment-applications.index', [
            'institution' => $this->institution->uuid,
            'vacancyPost' => $vacancyPost->id,
        ]))
        ->assertForbidden();

    actingAs($this->admin)
        ->getJson(route('institutions.recruitment-applications.index', [
            'institution' => $this->institution->uuid,
            'vacancyPost' => $vacancyPost->id,
        ]))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $assert) => $assert
                ->component('institutions/recruitment/list-recruitment-applications')
                ->has('recruitmentApplications.data', 1)
        );

    actingAs($this->admin)
        ->postJson(route('institutions.recruitment-applications.update-status', [
            'institution' => $this->institution->uuid,
            'recruitmentApplication' => $application->id,
        ]), [
            'status' => RecruitmentApplicationStatus::Shortlisted->value,
            'review_note' => 'Invite for CBT screening.',
        ])
        ->assertOk();

    assertDatabaseHas('recruitment_applications', [
        'id' => $application->id,
        'status' => RecruitmentApplicationStatus::Shortlisted->value,
        'review_note' => 'Invite for CBT screening.',
    ]);
});
