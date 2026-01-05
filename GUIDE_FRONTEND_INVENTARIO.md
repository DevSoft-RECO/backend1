# Guía de Implementación Frontend: Módulo Inventario de Software

Esta guía detalla cómo consumir el endpoint de **Inventario de Software** (`/api/inventario-software`) desde el cliente (Frontend), manejando correctamente la paginación y los filtros.

## 1. Estructura de Datos (Objeto Software)

Cada ítem que recibirás en el array de datos tendrá esta estructura:

```json
{
  "id": 1,
  "nombre": "Microsoft Office 2021",
  "enlace": "https://office.com/setup",
  "descripcion": "Licencia volumen estandar",
  "tipo": "Ofimatica",
  "usuario": "admin@empresa.com",
  "clave": "A1B2-C3D4-E5F6",
  "created_at": "2026-01-05T17:30:00.000000Z",
  "updated_at": "2026-01-05T17:30:00.000000Z"
}
```

> **Nota**: `usuario` y `clave` son campos de texto simple (alfanuméricos).

---

## 2. Consumo del Endpoint (Listado)

El método `index` está paginado. **No recibirás un array simple**, sino un objeto de paginación de Laravel.

**Petición:**
`GET /api/inventario-software`

**Parámetros Soportados (Query Params):**

| Parámetro | Tipo | Descripción |
| :--- | :--- | :--- |
| `page` | `int` | Número de página actual (por defecto 1). |
| `per_page`| `int` | (Opcional) Cantidad de ítems por página (default 20 del lado del server). |
| `nombre` | `string` | Filtra coincidencias parciales en el nombre. |
| `tipo` | `string` | Filtra coincidencia exacta por tipo. |

**Ejemplo de URL con filtros:**
`/api/inventario-software?page=2&nombre=Office&tipo=Licencia`

### Estructura de la Respuesta (JSON)

```json
{
    "current_page": 1,
    "data": [
        { "id": 1, "nombre": "Software A", ... },
        { "id": 2, "nombre": "Software B", ... }
    ],
    "first_page_url": "http://.../api/inventario-software?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://.../api/inventario-software?page=5",
    "links": [ ... ],
    "next_page_url": "http://.../api/inventario-software?page=2",
    "path": "http://.../api/inventario-software",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 100
}
```

---

## 3. Ejemplo de Implementación (Vue.js + Axios)

A continuación un ejemplo de cómo estructurar esto en tu componente Vue:

```javascript
<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios'; // O tu instancia configurada de axios

// Estados
const softwareList = ref([]);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 20
});

// Filtros
const filters = ref({
    nombre: '',
    tipo: ''
});

const isLoading = ref(false);

// Función para obtener datos
const fetchSoftware = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {
            page: page,
            nombre: filters.value.nombre, // Se envía solo si tiene valor
            tipo: filters.value.tipo
        };

        const response = await axios.get('/api/inventario-software', { params });
        
        // Asignamos los datos puros a la lista
        softwareList.value = response.data.data;
        
        // Actualizamos meta-data de paginación
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total,
            per_page: response.data.per_page
        };

    } catch (error) {
        console.error("Error cargando inventario:", error);
    } finally {
        isLoading.value = false;
    }
};

// Cargar al montar
onMounted(() => {
    fetchSoftware();
});

// Ejemplo: Ejecutar búsqueda cuando el usuario escribe (con debounce idealmente)
const onSearch = () => {
    // Resetear a página 1 al filtrar
    fetchSoftware(1);
};
</script>
```

### Tips para la Paginación en la UI

Debes generar botones basados en `pagination.current_page` y `pagination.last_page`.

*   **Botón "Anterior"**: Deshabilitado si `current_page === 1`. Al clickear llama a `fetchSoftware(current_page - 1)`.
*   **Botón "Siguiente"**: Deshabilitado si `current_page === last_page`. Al clickear llama a `fetchSoftware(current_page + 1)`.

---

## 4. Métodos CRUD (Referencias)

Para las demás operaciones, usa rutas estándar:

*   **Crear**: `POST /api/inventario-software`
    *   Body: `{ nombre: '...', tipo: '...', ... }`
*   **Ver**: `GET /api/inventario-software/{id}`
*   **Editar**: `PUT /api/inventario-software/{id}`
    *   Body: `{ nombre: '...', ... }`
*   **Eliminar**: `DELETE /api/inventario-software/{id}`
