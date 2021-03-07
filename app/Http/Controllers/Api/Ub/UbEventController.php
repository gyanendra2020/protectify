<?php

namespace App\Http\Controllers\Api\Ub;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ub\UbEventResource;
use App\Models\Ub\UbEvent;
use App\Models\Ub\UbPage;
use Illuminate\Http\Request;

class UbEventController extends Controller
{
    public function list(Request $request, $ubPageKey)
    {
        $ubPage = UbPage::where('key', $ubPageKey)->firstOrFail();

        $input = $request->validate([
            'from_id' => 'integer|min:0',
            'till_time' => 'integer|min:0',
        ]);

        $input['from_id'] = $input['from_id'] ?? 0;
        $input['till_time'] = $input['till_time'] ?? 10000;

        $ubEventQuery = $ubPage->events();
        $ubEventQuery->where('id', '>', $input['from_id']);
        $ubEventQuery->where('time', '<', $input['till_time']);
        $ubEventQuery->orderBy('id', 'asc');
        $ubEvents = $ubEventQuery->get();

        return UbEventResource::collection($ubEvents);
    }

    public function create(Request $request, $ubPageKey)
    {
        $ubPage = UbPage::where('key', $ubPageKey)->firstOrFail();

        $input = $request->validate([
            '*' => 'required|array',
            '*.index' => 'required|integer|min:0',
            '*.time' => 'required|integer|min:0',
            '*.type' => 'required|string',
            '*.path' => 'string|regex:/^[a-z]+$/i|nullable',
            '*.name' => 'string|nullable',
            '*.data' => 'array|nullable',
        ]);

        $lastInputEvent = count($input) > 0 ? $input[count($input) - 1] : null;

        $insertedUbEventsCount = UbEvent::insertOrIgnore(array_map(function ($inputEvent) use ($ubPage) {
            return [
                'page_id' => $ubPage->id,
                'index' => $inputEvent['index'],
                'time' => $inputEvent['time'],
                'type' => $inputEvent['type'],
                'path' => $inputEvent['path'] ?? null,
                'name' => $inputEvent['name'] ?? null,
                'data' => isset($inputEvent['data']) ? json_encode($inputEvent['data']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $input));

        if ($lastInputEvent) {
            $ubPage->duration = max($ubPage->duration, $lastInputEvent['time'] + 1000);
            $ubPage->save();
        }

        return response()->json(['data' => $insertedUbEventsCount], 201);
    }
}
