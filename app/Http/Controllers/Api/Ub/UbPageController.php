<?php

namespace App\Http\Controllers\Api\Ub;

use Exception;
use App\Models\User;
use App\Models\Ub\UbPage;
use App\Models\Ub\UbVisitor;
use Illuminate\Http\Request;
use App\Models\Ub\UbResource;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Ub\UbPageResource;
use App\Http\Requests\UbPageDeleteRequest;

class UbPageController extends Controller
{
    public function create(Request $request, User $user)
    {
        if ($user->is_disabled) {
            abort(403, 'This user is temporary disabled.');
        }

        $input = $request->validate([
            'visitor' => 'required|array',
            'visitor.key' => 'string|exists:ub_visitors,key',
            'visitor.user_agent' => 'required_without:visitor.key|string',
            'url' => 'required|url',
            'title' => 'required|string',
            'initial_state' => 'required|array',
            'html' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            if (!$ubVisitor = UbVisitor::where('key', $input['visitor']['key'] ?? null)->first()) {
                $ubVisitor = new UbVisitor;
                $ubVisitor->user_id = $user->id;
                $ubVisitor->fill($input['visitor']);
                $ubVisitor->generateKey();
            }

            $ubVisitor->last_ip = $request->ip();
            $ubVisitor->save();

            $ubPage = new UbPage;
            $ubPage->user_id = $user->id;
            $ubPage->visitor_id = $ubVisitor->id;
            $ubPage->fill($input);
            $ubPage->generateKey();
            $ubPage->visitor_ip = $request->ip();
            $ubPage->save();
            $ubPage->setRelation('visitor', $ubVisitor);
            // $ubPage->storeHTMLResources($input['url'], $input['html']);

            $ubResource = new UbResource;
            $ubResource->page_id = $ubPage->id;
            $ubResource->url = $input['url'] . '#snapshot-0';
            $ubResource->hash = md5($ubResource->url);
            $ubResource->mime = 'text/html';
            $ubResource->path = 'snapshots/0.html';
            $ubResource->status = 200;
            $ubResource->save();

            Storage::disk('public')->put('ub-pages/' . $ubPage->storage_path . 'snapshots/0.html', $input['html']);
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }

        DB::commit();

        return new UbPageResource($ubPage);
    }

    public function delete(UbPageDeleteRequest $request, UbPage $ubPage)
    {
        $ubPage->delete();

        return new UbPageResource($ubPage);
    }
}
