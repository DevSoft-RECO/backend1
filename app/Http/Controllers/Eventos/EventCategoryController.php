<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Http\Resources\Eventos\EventCategoryResource;
use App\Models\Eventos\EventCategory;
use Illuminate\Http\Request;

class EventCategoryController extends Controller
{
    public function index()
    {
        $categories = EventCategory::where('is_active', true)->get();
        return EventCategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $category = EventCategory::create($validated);
        return new EventCategoryResource($category);
    }

    public function show(EventCategory $eventCategory)
    {
        return new EventCategoryResource($eventCategory);
    }

    public function update(Request $request, EventCategory $eventCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
        ]);

        $eventCategory->update($validated);
        return new EventCategoryResource($eventCategory);
    }

    public function destroy(EventCategory $eventCategory)
    {
        $eventCategory->delete();
        return response()->noContent();
    }
}
