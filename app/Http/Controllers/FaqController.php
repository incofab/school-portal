<?php

namespace App\Http\Controllers;

use App\Enums\FaqType;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqController extends Controller
{
  public function index(Request $request)
  {
    return $this->render($request, FaqType::KnowledgeBase);
  }

  public function faqs(Request $request)
  {
    return $this->render($request, FaqType::Faq);
  }

  private function render(Request $request, FaqType $type)
  {
    $faqs = ($type === FaqType::Faq
      ? Faq::query()->faqs()
      : Faq::query()->knowledgeBase())
      ->active()
      ->when($request->string('search')->toString(), function (
        $query,
        string $search
      ) {
        $query->where(function ($query) use ($search) {
          $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%")
            ->orWhere('type', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
        });
      })
      ->ordered()
      ->get();

    return Inertia::render('knowledge-base', [
      'faqs' => $faqs,
      'search' => $request->string('search')->toString(),
      'contentType' => $type->value
    ]);
  }
}
