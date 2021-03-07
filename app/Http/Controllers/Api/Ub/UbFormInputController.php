<?php

namespace App\Http\Controllers\Api\Ub;

use App\Models\Ub\UbPage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Ub\UbFromInputResource;
use App\Models\Ub\UbForm;
use App\Models\Ub\UbFormInput;

class UbFormInputController extends Controller
{
    public function create(Request $request, $ubPageKey)
    {
        $ubPage = UbPage::where('key', $ubPageKey)->firstOrFail();

        $input = $request->validate([
            '*' => 'required|array',
            '*.form' => 'required|array',
            '*.form.ub_id' => 'required|integer',
            '*.name' => 'required|string',
            '*.title' => 'present|string|nullable',
            '*.type' => 'required|string',
            '*.value' => 'present|string|nullable',
        ]);

        $ubForms = $ubPage->forms()->whereIn('ub_id', collect($input)->pluck('form.ub_id')->unique()->values())->get();

        foreach ($input as $inputFormInput) {
            if (!$ubForm = $ubForms->where('ub_id', $inputFormInput['form']['ub_id'])->first()) {
                $ubForm = new UbForm;
                $ubForm->page_id = $ubPage->id;
                $ubForm->ub_id = $inputFormInput['form']['ub_id'];
                $ubForm->save();
                $ubForms->push($ubForm);
            }

            if (!$ubFormInput = $ubForm->inputs()->where('name', $inputFormInput['name'])->first()) {
                $ubFormInput = new UbFormInput;
                $ubFormInput->page_id = $ubPage->id;
                $ubFormInput->form_id = $ubForm->id;
            }

            $ubFormInput->fill($inputFormInput);
            $ubFormInput->save();
        }

        return response()->json(['data' => null], 201);
    }
}
