<?php

namespace App\Http\Controllers\Managers\Faqs;

use App\Enums\FaqType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqController extends Controller
{
  public function index(Request $request)
  {
    $this->authorize('viewAny', Faq::class);

    $query = Faq::query()
      ->when(
        FaqType::tryFrom($request->string('type')->toString()),
        fn($query, FaqType $type) => $query->where('type', $type->value)
      )
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
      ->ordered();

    return Inertia::render('managers/faqs/list-faqs', [
      'faqs' => paginateFromRequest($query)
    ]);
  }

  public function create()
  {
    $this->authorize('create', Faq::class);

    return Inertia::render('managers/faqs/create-edit-faq');
  }

  public function store(FaqRequest $request)
  {
    $this->authorize('create', Faq::class);

    $faq = Faq::query()->create($request->validated());

    return $this->ok(['faq' => $faq]);
  }

  public function show(Faq $faq)
  {
    $this->authorize('view', $faq);

    return Inertia::render('managers/faqs/show-faq', [
      'faq' => $faq
    ]);
  }

  public function edit(Faq $faq)
  {
    $this->authorize('update', $faq);

    return Inertia::render('managers/faqs/create-edit-faq', [
      'faq' => $faq
    ]);
  }

  public function update(FaqRequest $request, Faq $faq)
  {
    $this->authorize('update', $faq);

    $faq->fill($request->validated())->save();

    return $this->ok(['faq' => $faq]);
  }

  public function toggle(Faq $faq)
  {
    $this->authorize('update', $faq);

    $faq->forceFill(['is_active' => !$faq->is_active])->save();

    return $this->ok(['faq' => $faq]);
  }

  public function destroy(Faq $faq)
  {
    $this->authorize('delete', $faq);

    $faq->delete();

    return $this->ok();
  }
}
