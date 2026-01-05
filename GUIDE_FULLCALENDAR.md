# Guía de Implementación Frontend: Calendario de Eventos (FullCalendar)

Esta guía detalla cómo consumir la API de `/api/events` y `/api/event-categories` utilizando **FullCalendar** en Vue 3.

## 1. Instalación de Dependencias

Asegúrate de tener instalados los paquetes necesarios:

```bash
npm install @fullcalendar/vue3 @fullcalendar/core @fullcalendar/daygrid @fullcalendar/interaction @fullcalendar/timegrid @fullcalendar/list
```

## 2. Consumo de la API

### Formato de Respuesta del Backend (`EventResource`)
El backend ya devuelve los campos formateados para FullCalendar:

```json
{
  "id": 1,
  "title": "Reunión General",
  "start": "2026-01-05T10:00:00",
  "end": "2026-01-05T12:00:00",
  "color": "#3b82f6",       // Color de fondo (viene de la categoría)
  "text_color": "#ffffff",  // Color de texto (viene de la categoría)
  "extendedProps": {
      "description": "Detalles...",
      "location": "Sala 1",
      "category": { ... },
      "user_id": 1
  }
}
```

> **Nota**: El backend mapea automáticamente `color` y `text_color` desde la categoría asociada. FullCalendar usa estas propiedades nativamente para pintar el evento.

---

## 3. Componente Vue (Ejemplo Completo)

Esté ejemplo muestra cómo cargar eventos dinámicamente usando la prop `events` como función (la forma más eficiente).

```javascript
<template>
  <div class="calendar-app">
    <!-- Componente FullCalendar -->
    <FullCalendar :options="calendarOptions" />
    
    <!-- Modal para Crear/Editar (Pseudo-código) -->
    <EventModal 
        v-if="showModal" 
        :event="selectedEvent" 
        @save="refreshCalendar" 
        @close="showModal = false" 
    />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import axios from 'axios'; // Tu instancia de axios configurada

const calendarRef = ref(null);
const showModal = ref(false);
const selectedEvent = ref(null);

// Opciones del Calendario
const calendarOptions = reactive({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
    },
    editable: true,
    selectable: true,
    
    // 1. CARGA DE EVENTOS (Función Dinámica)
    // FullCalendar llama a esto cada vez que cambia de mes/semana
    events: async (fetchInfo, successCallback, failureCallback) => {
        try {
            const params = {
                start_date: fetchInfo.startStr, // ISO string enviado por FullCalendar
                end_date: fetchInfo.endStr,     // ISO string enviado por FullCalendar
                // event_category_id: filtroCategoria.value // (Opcional) Si tienes un select externo
            };

            const response = await axios.get('/api/events', { params });
            
            // La API retorna { data: [...] } por el Resource Collection
            // Mapeamos si es necesario, aunque el resource ya viene listo
            const events = response.data.data.map(event => ({
                id: event.id,
                title: event.title,
                start: event.start,
                end: event.end,
                backgroundColor: event.color, // FullCalendar v5+ usa backgroundColor o color
                borderColor: event.color,     // Para que el borde combine
                textColor: event.text_color,
                allDay: event.is_all_day,
                extendedProps: {
                    description: event.description,
                    location: event.location,
                    category: event.category
                }
            }));

            successCallback(events);
        } catch (error) {
            console.error(error);
            failureCallback(error);
        }
    },

    // 2. CREAR EVENTO (Click en fecha vacía)
    select: (selectInfo) => {
        selectedEvent.value = {
            id: null,
            title: '',
            start: selectInfo.startStr,
            end: selectInfo.endStr,
            is_all_day: selectInfo.allDay,
            category_id: null
        };
        showModal.value = true;
    },

    // 3. EDITAR EVENTO (Click en evento existente)
    eventClick: (clickInfo) => {
        const props = clickInfo.event.extendedProps;
        selectedEvent.value = {
            id: clickInfo.event.id,
            title: clickInfo.event.title,
            start: clickInfo.event.startStr, // Ojo con formatos de fecha para inputs datetime-local
            end: clickInfo.event.endStr,
            description: props.description,
            location: props.location,
            event_category_id: props.category?.id // Acceder a props anidados
        };
        showModal.value = true;
    },

    // 4. ARRASTRAR Y SOLTAR (Update rápido)
    eventDrop: async (dropInfo) => {
        await updateEventDate(dropInfo.event);
    },
    eventResize: async (resizeInfo) => {
        await updateEventDate(resizeInfo.event);
    }
});

// Helper para actualizar solo fechas al arrastrar
const updateEventDate = async (apiEvent) => {
    try {
        await axios.put(`/api/events/${apiEvent.id}`, {
            start: apiEvent.start.toISOString(),
            end: apiEvent.end?.toISOString() || apiEvent.start.toISOString(), // end puede ser null si es allDay
            is_all_day: apiEvent.allDay
        });
        // Not success notification...
    } catch (error) {
        console.error(error);
        apiEvent.revert(); // Revertir cambio visual si falla
    }
};

const refreshCalendar = () => {
    // Método para recargar eventos sin recargar pagina
    // Necesitas ref al componente <FullCalendar ref="calendarRef" ... />
    // calendarRef.value.getApi().refetchEvents();
    // O si usas options reactivas, a veces es tricky. 
    // Lo más fácil con Vue 3 composition API es forzar re-render o usar la API del plugin.
    document.querySelector('.fc').__fullCalendarApi.refetchEvents(); 
};
</script>
```

---

## 4. Gestión de Categorías

Para poblar el `<select>` en tu modal de creación/edición:

```javascript
const categories = ref([]);

const loadCategories = async () => {
    const res = await axios.get('/api/event-categories');
    categories.value = res.data.data;
};
```

Usa el `id` de la categoría para enviarlo como `event_category_id` en el POST/PUT de eventos.
