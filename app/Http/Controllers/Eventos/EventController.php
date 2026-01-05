<?php

namespace App\Http\Controllers\Eventos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Eventos\StoreEventRequest;
use App\Http\Requests\Eventos\UpdateEventRequest;
use App\Http\Resources\Eventos\EventResource;
use App\Models\Eventos\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with('category');

        // Filtrado por fecha (overlap)
        if ($request->has(['start_date', 'end_date'])) {
            $start = \Carbon\Carbon::parse($request->input('start_date'));
            $end = \Carbon\Carbon::parse($request->input('end_date'));

            $query->where(function($q) use ($start, $end) {
                $q->where('start', '<', $end)
                  ->where('end', '>', $start);
            });
        }

        // Filtro opcional por categoría
        if ($request->has('event_category_id')) {
            $query->where('event_category_id', $request->input('event_category_id'));
        }

        return EventResource::collection($query->get());
    }

    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();

        // Asumiendo que tu Guard de autenticación (JWT/Token) resuelve el usuario temporalmente
        // Si no, extrae el ID del token decodificado directamente.
        $data['user_id'] = Auth::id() ?? $request->user()->id;

        $event = Event::create($data);
        $event->load('category');

        return new EventResource($event);
    }

    public function show(Event $event)
    {
        $event->load('category');
        return new EventResource($event);
    }

    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->validated());
        $event->load('category');

        return new EventResource($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->noContent();
    }
}
