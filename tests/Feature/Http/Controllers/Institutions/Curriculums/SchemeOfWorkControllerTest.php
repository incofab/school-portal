<?php

namespace Tests\Feature\Http\Controllers\Institutions\Curriculums;

use App\Models\Institution;
use App\Models\SchemeOfWork;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Enums\InstitutionUserType;

class SchemeOfWorkControllerTest extends TestCase
{
    // use RefreshDatabase;

    protected $admin;
    protected $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->institution = Institution::factory()->create();
        $this->institution->users()->attach($this->admin, ['type' => InstitutionUserType::Admin]);
    }

    /** @test */
    public function admin_can_view_create_scheme_of_work_page()
    {
        $topic = Topic::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('institutions.scheme-of-works.create', [
                'institution' => $this->institution,
                'topic' => $topic,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert
            ->component('institutions/scheme-of-works/create-edit-scheme-of-work')
            ->has('topicId')
        );
    }

    /** @test */
    public function admin_can_view_edit_scheme_of_work_page()
    {
        $schemeOfWork = SchemeOfWork::factory()->create(['institution_id' => $this->institution->id]);

        $response = $this->actingAs($this->admin)
            ->get(route('institutions.scheme-of-works.edit', [
                'institution' => $this->institution,
                'schemeOfWork' => $schemeOfWork,
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($assert) => $assert
            ->component('institutions/scheme-of-works/create-edit-scheme-of-work')
            ->has('parentTopics')
            ->has('schemeOfWork')
        );
    }

    /** @test */
    public function admin_can_store_new_scheme_of_work()
    {
        $topic = Topic::factory()->create();

        $schemeOfWorkData = [
            'term' => 'First Term',
            'topic_id' => $topic->id,
            'week_number' => 1,
            'learning_objectives' => 'Test objectives',
            'resources' => 'Test resources',
            'is_used_by_institution_group' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('institutions.scheme-of-works.store', ['institution' => $this->institution]), $schemeOfWorkData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('scheme_of_works', [
            'term' => 'First Term',
            'topic_id' => $topic->id,
            'institution_id' => $this->institution->id,
        ]);
    }

    /** @test */
    public function admin_can_update_scheme_of_work()
    {
        $schemeOfWork = SchemeOfWork::factory()->create(['institution_id' => $this->institution->id]);
        $newTopic = Topic::factory()->create();

        $updatedData = [
            'term' => 'Second Term',
            'topic_id' => $newTopic->id,
            'week_number' => 2,
            'learning_objectives' => 'Updated objectives',
            'resources' => 'Updated resources',
            'is_used_by_institution_group' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('institutions.scheme-of-works.update', [
                'institution' => $this->institution,
                'schemeOfWork' => $schemeOfWork,
            ]), $updatedData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('scheme_of_works', [
            'id' => $schemeOfWork->id,
            'term' => 'Second Term',
            'topic_id' => $newTopic->id,
        ]);
    }

    /** @test */
    public function admin_can_delete_scheme_of_work_without_lesson_plans()
    {
        $schemeOfWork = SchemeOfWork::factory()->create(['institution_id' => $this->institution->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('institutions.scheme-of-works.destroy', [
                'institution' => $this->institution,
                'schemeOfWork' => $schemeOfWork,
            ]));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('scheme_of_works', ['id' => $schemeOfWork->id]);
    }

    /** @test */
    public function admin_cannot_delete_scheme_of_work_with_lesson_plans()
    {
        $schemeOfWork = SchemeOfWork::factory()->create(['institution_id' => $this->institution->id]);
        $schemeOfWork->lessonPlans()->create(['title' => 'Test Lesson Plan']);

        $response = $this->actingAs($this->admin)
            ->delete(route('institutions.scheme-of-works.destroy', [
                'institution' => $this->institution,
                'schemeOfWork' => $schemeOfWork,
            ]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('scheme_of_works', ['id' => $schemeOfWork->id]);
    }

    /** @test */
    public function non_admin_cannot_access_scheme_of_work_routes()
    {
        $nonAdmin = User::factory()->create();
        $this->institution->users()->attach($nonAdmin, ['type' => InstitutionUserType::Teacher]);

        $topic = Topic::factory()->create();
        $schemeOfWork = SchemeOfWork::factory()->create(['institution_id' => $this->institution->id]);

        $createResponse = $this->actingAs($nonAdmin)
            ->get(route('institutions.scheme-of-works.create', [
                'institution' => $this->institution,
                'topic' => $topic,
            ]));

        $editResponse = $this->actingAs($nonAdmin)
            ->get(route('institutions.scheme-of-works.edit', [
                'institution' => $this->institution,
                'schemeOfWork' => $schemeOfWork,
            ]));

        $storeResponse = $this->actingAs($nonAdmin)
            ->post(route('institutions.scheme-of-works.store', ['institution' => $this->institution]), []);

        $updateResponse = $this->actingAs($nonAdmin)
            ->put(route('institutions.scheme-of-works.update', [
                'institution' => $this->institution,
                'schemeOfWork' => $schemeOfWork,
            ]), []);

        $deleteResponse = $this->actingAs($nonAdmin)
            ->delete(route('institutions.scheme-of-works.destroy', [
                'institution' => $this->institution,
                'schemeOfWork' => $schemeOfWork,
            ]));

        $createResponse->assertStatus(403);
        $editResponse->assertStatus(403);
        $storeResponse->assertStatus(403);
        $updateResponse->assertStatus(403);
        $deleteResponse->assertStatus(403);
    }
}