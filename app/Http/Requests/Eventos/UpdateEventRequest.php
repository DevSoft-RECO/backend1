<?php

namespace App\Http\Requests\Eventos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_category_id' => 'nullable|exists:event_categories,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start' => 'sometimes|required|date',
            'end' => 'sometimes|required|date|after_or_equal:start',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:scheduled,completed,cancelled',
        ];
    }
}
