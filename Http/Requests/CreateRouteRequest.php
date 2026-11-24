<?php

declare(strict_types=1);

namespace Modules\RouteRequest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\RouteRequest\Support\SchemaHelper;

class CreateRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        if (!class_exists(SchemaHelper::class, false)) {
            require_once __DIR__ . '/../../Support/SchemaHelper.php';
        }
        SchemaHelper::ensureRouteRequestsColumns();

        if ($this->has('dep_airport_id') && $this->input('dep_airport_id') !== null) {
            $this->merge([
                'dep_airport_id' => strtoupper(trim((string) $this->input('dep_airport_id'))),
            ]);
        }

        $isMulti  = filter_var($this->input('multiple_destinations', false), FILTER_VALIDATE_BOOLEAN);
        $isReturn = filter_var($this->input('request_return', false), FILTER_VALIDATE_BOOLEAN);

        $arrInput = $this->input('arr_airport_id');
        if (!$isMulti && $arrInput !== null && trim((string) $arrInput) !== '') {
            $this->merge([
                'arr_airport_id' => strtoupper(trim((string) $arrInput)),
            ]);
        } else {
            $this->merge([
                'arr_airport_id' => null,
            ]);
        }

        $this->merge([
            'multiple_destinations' => $isMulti,
            'request_return'        => $isReturn,
        ]);
    }

    public function rules(): array
    {
        $user = Auth::user();

        $allowedTypeRatingIds = $user && $user->typeratings
            ? $user->typeratings->where('active', true)->pluck('id')->toArray()
            : [];

        return [
            'typerating_id'           => [
                'required',
                'integer',
                Rule::in($allowedTypeRatingIds),
            ],
            'realworld_airline'       => ['required', 'string', 'max:150'],
            'realworld_airline_other' => ['nullable', 'required_if:realworld_airline,OTHER', 'string', 'max:150'],
            'dep_airport_id'          => ['required', 'string', 'min:3', 'max:5', 'exists:airports,id'],
            'arr_airport_id'          => [
                'nullable',
                'string',
                'min:3',
                'max:5',
                'exists:airports,id',
                function ($attribute, $value, $fail) {
                    if ($this->boolean('multiple_destinations') && !empty($value)) {
                        $fail('When "Multiple Destinations" is checked, the Ending Airport must be left blank (ANY / Open Destination).');
                    }
                },
            ],
            'multiple_destinations'   => ['nullable', 'boolean'],
            'request_return'          => ['nullable', 'boolean'],
            'recipient_email'         => ['nullable', 'email', 'max:191'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
        ];
    }

    #[\Override]
    public function messages(): array
    {
        return [
            'typerating_id.required'              => 'Please select one of your signed-off Type Ratings.',
            'typerating_id.in'                    => 'You are not signed off or authorized for the selected Type Rating.',
            'realworld_airline.required'          => 'Please select the real-world or charter airline counterpart being simulated.',
            'realworld_airline_other.required_if' => 'Please enter the name of the real-world/charter airline.',
            'dep_airport_id.required'             => 'A starting (departure) airport ICAO code is required.',
            'dep_airport_id.exists'               => 'The selected Starting Airport does not exist in our phpVMS airports database. Please select an airport from the autocomplete list.',
            'arr_airport_id.exists'               => 'The selected Ending Airport does not exist in our phpVMS airports database. Please select an airport from the autocomplete list or leave it blank.',
        ];
    }
}
