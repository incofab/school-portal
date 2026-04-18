<?php

namespace App\Http\Controllers\Managers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UITableFilters\UserUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListUsersController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = User::query()
            ->select('users.*')
            ->with(['roles', 'institutionUsers.institution']);

        UserUITableFilters::make($request->all(), $query)->filterQuery();

        $query->when(! $request->sortKey, fn ($q) => $q->latest('users.id'));

        return Inertia::render('users/list-users', [
            'users' => paginateFromRequest($query->distinct()),
        ]);
    }
}
