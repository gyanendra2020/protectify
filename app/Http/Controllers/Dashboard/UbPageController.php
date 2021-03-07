<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use App\Models\Ub\UbPage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UbPageController extends Controller
{
    public function list(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $perPage = max($perPage, 25);
        $perPage = min($perPage, 250);
        $filter = $request->input('filter', []);
        $filter['url'] = trim($filter['url'] ?? '');
        $filter['query'] = trim($filter['query'] ?? '');
        $filter['user_id'] = (int) ($filter['user_id'] ?? '0');
        $filter['user'] = User::find($filter['user_id']);

        if (in_array(auth()->user()->role, [User::ROLE_ADMIN])) {
            $ubPageQuery = UbPage::query();
            $ubPageQuery->with(['user']);
        } else {
            $ubPageQuery = auth()->user()->ub_pages();
        }

        $ubPageQuery = $ubPageQuery->select('ub_pages.*');

        if ($filter['url']) {
            $ubPageQuery->where('url', 'like', '%' . $filter['url'] . '%');
        }

        if ($filter['query']) {
            $ubPageQuery->join('ub_form_inputs', 'ub_form_inputs.page_id', '=', 'ub_pages.id');

            $ubPageQuery->where(function ($where) use ($filter) {
                $where->orWhere('ub_form_inputs.value', $filter['query']);

                $where->orWhere(function ($where) use ($filter) {
                    $filterQueryWords = preg_split('/\s+/', $filter['query']);

                    foreach ($filterQueryWords as $filterQueryWord) {
                        $where->where('ub_form_inputs.value', 'like', '%' . $filterQueryWord . '%');
                        // $where->whereRaw('MATCH (ub_form_inputs.value) AGAINST (? IN BOOLEAN MODE)', [$filterQueryWord . '*']);
                    }
                });
            });

            $ubPageQuery->groupBy('ub_pages.id');
        }

        if ($filter['user_id']) {
            $ubPageQuery->where('ub_pages.user_id', $filter['user_id']);
        }

        $ubPageQuery->orderBy('ub_pages.id', 'desc');
        $ubPages = $ubPageQuery->paginate($perPage);

        return view('dashboard.ub_pages', [
            'ubPages' => $ubPages,
            'filter' => $filter,
            'doAllowToFilterByUser' => in_array(auth()->user()->role, [User::ROLE_ADMIN]),
            'doAllowToDelete' => in_array(auth()->user()->role, [User::ROLE_ADMIN]),
            'doShowUserColumn' => in_array(auth()->user()->role, [User::ROLE_ADMIN]),
            'perPage' => $perPage,
        ]);
    }

    public function view($ubPageId)
    {
        $ubPage = UbPage::with('forms', 'forms.inputs')->findOrFail($ubPageId);

        return view('dashboard.ub_page', [
            'ubPage' => $ubPage,
        ]);
    }
}
