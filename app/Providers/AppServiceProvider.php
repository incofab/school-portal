<?php

namespace App\Providers;

use App\Contracts\AI\AssistantTextGenerator;
use App\Contracts\AI\KnowledgeRetriever;
use App\Observers\ModelAuditObserver;
use App\Services\AI\AssistantActionRegistry;
use App\Services\AI\AssistantToolRegistry;
use App\Services\AI\Knowledge\DatabaseKnowledgeRetriever;
use App\Services\AI\LaravelAiAssistantTextGenerator;
use App\Services\AI\Tools\CreateClassActionTool;
use App\Services\AI\Tools\CreateStaffActionTool;
use App\Services\AI\Tools\CreateSubjectActionTool;
use App\Services\AI\Tools\ListAcademicSessionsTool;
use App\Services\AI\Tools\ListAuthorizedStaffTool;
use App\Services\AI\Tools\ListAuthorizedStudentsTool;
use App\Services\AI\Tools\ListAuthorizedTeacherAssignmentsTool;
use App\Services\AI\Tools\ListClassesTool;
use App\Services\AI\Tools\SendInstitutionNotificationActionTool;
use App\Services\AI\Tools\UpdateClassActionTool;
use App\Services\AI\Tools\UpdateStaffActionTool;
use App\Services\AI\Tools\ViewInstitutionProfileTool;
use App\Services\AI\Tools\ViewClassAttendanceSummaryTool;
use App\Services\AI\Tools\ViewClassPerformanceSummaryTool;
use App\Services\AI\Tools\ViewPublishedStudentResultTool;
use App\Services\AI\Tools\ViewStudentAttendanceSummaryTool;
use App\Services\AI\Tools\ViewStudentFeeSummaryTool;
use App\Support\Audit\ModelAuditRegistry;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    $this->app->bind(
      AssistantTextGenerator::class,
      LaravelAiAssistantTextGenerator::class
    );
    $this->app->bind(
      KnowledgeRetriever::class,
      DatabaseKnowledgeRetriever::class
    );
    $this->app->singleton(AssistantToolRegistry::class, function ($app) {
      return new AssistantToolRegistry([
        $app->make(ViewInstitutionProfileTool::class),
        $app->make(ListAcademicSessionsTool::class),
        $app->make(ListAuthorizedStaffTool::class),
        $app->make(ListClassesTool::class),
        $app->make(ListAuthorizedStudentsTool::class),
        $app->make(ViewPublishedStudentResultTool::class),
        $app->make(ViewStudentAttendanceSummaryTool::class),
        $app->make(ViewClassAttendanceSummaryTool::class),
        $app->make(ViewStudentFeeSummaryTool::class),
        $app->make(ViewClassPerformanceSummaryTool::class),
        $app->make(ListAuthorizedTeacherAssignmentsTool::class)
      ]);
    });
    $this->app->singleton(AssistantActionRegistry::class, function ($app) {
      return new AssistantActionRegistry([
        $app->make(CreateClassActionTool::class),
        $app->make(UpdateClassActionTool::class),
        $app->make(CreateSubjectActionTool::class),
        $app->make(CreateStaffActionTool::class),
        $app->make(UpdateStaffActionTool::class),
        $app->make(SendInstitutionNotificationActionTool::class)
      ]);
    });
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    Relation::enforceMorphMap(MorphMap::MAP);

    foreach (ModelAuditRegistry::models() as $model) {
      $model::observe(ModelAuditObserver::class);
    }

    $this->allowMultiDomain();
  }

  /**
   * Set cookies base on the calling domain since multiple domains will be pointed to this application
   */
  private function allowMultiDomain()
  {
    $host = request()->getHost();
    $name = Str::slug($host, '_') . '_session';
    Config::set('session.cookie', $name);
  }
}
