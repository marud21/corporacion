# Vista de Detalles del Socio - Documentación

## 📋 Resumen de Cambios

Se ha implementado exitosamente la funcionalidad para visualizar detalles completos de un socio en un modal cuando se hace clic en el botón "Ver" en la tabla.

## 🎯 Características Implementadas

### 1. Botón "Ver" en la Tabla
- **Ubicación**: Columna de "Acciones" en la tabla de socios
- **Icono**: `<i class="fas fa-eye"></i>` (ojo)
- **Color**: btn-info (azul)
- **Atributo**: `data-id` con el ID del socio
- **Archivo**: `socios.php` líneas ~191-197

### 2. Modal de Detalles (verSocioModal)
**Estructura:**
- **Encabezado**: "Detalles del Socio"
- **Cuerpo**: Dos columnas
  - **Columna Izquierda**: Foto del documento de identidad
  - **Columna Derecha**: Información completa del socio

**Campos Mostrados:**
- **Información Personal**:
  - Nombre
  - Documento
  - Email
  - Teléfono
  - Fecha de Nacimiento
  - Dirección

- **Información de Afiliación**:
  - Fecha de Afiliación
  - Estado (activo/lesionado/retirado)
  - Entidad de Salud
  - Saldo/Deuda
  - Observaciones

**Pie del Modal**:
- Botón "Cerrar" (gris)
- Botón "Editar" (naranja, redirige al modal de edición)

**Archivo**: `socios.php` líneas ~320-520

### 3. Funciones JavaScript (public/js/socios.js)

#### a) `verSocioDetalles(id)`
**Propósito**: Cargar datos del socio y llenar el modal

**Flujo**:
1. Realiza GET request a `socio_controller.php?id=ID`
2. Recibe JSON con datos del socio
3. Llena todos los campos del modal:
   - Campos de texto con `textContent`
   - Formatea fechas a formato local
   - Capitaliza el estado
   - Formatea moneda con símbolo $
4. Maneja imagen:
   - Si `documento_pdf` existe: muestra imagen
   - Si no existe: muestra mensaje "No hay foto"
5. Guarda ID en botón de editar para uso posterior
6. Abre el modal con Bootstrap Modal API

**Ubicación**: Lines 345-400 en socios.js

#### b) Funciones Auxiliares
- `formatearFecha(fecha)`: Convierte fechas a formato local (ej: "1 de enero de 2024")
- `capitalizarPrimera(texto)`: Capitaliza primera letra (ej: "activo" → "Activo")
- `formatearMoneda(monto)`: Formatea con símbolo $ y separadores de miles

**Ubicación**: Lines 402-429 en socios.js

#### c) Event Listeners
- **Botón "Ver"**: Lines 55-59
  - Detecta clic en `.btn-ver`
  - Extrae `data-id`
  - Llama `verSocioDetalles(id)`

- **Botón "Editar" en Modal**: Lines 87-98
  - Ejecuta al hacer clic en `#btnEditarDesdeVer`
  - Cierra modal de ver
  - Llama `cargarSocioParaEditar(id)`
  - Abre modal de edición

**Ubicación**: socios.js DOMContentLoaded

## 🔄 Flujo de Ejecución

```
Usuario ve tabla de socios
    ↓
Hace clic en botón "Ver" (ojo azul)
    ↓
Event listener detecta .btn-ver
    ↓
verSocioDetalles(id) se ejecuta
    ↓
GET /socio_controller.php?id=ID
    ↓
API retorna JSON con datos del socio
    ↓
JavaScript llena todos los campos del modal
    ↓
Si documento_pdf existe → muestra imagen
    ↓
Si no existe → muestra "No hay foto"
    ↓
Modal se abre con Bootstrap
    ↓
Usuario puede:
  ├─ Cerrar modal (Cerrar)
  ├─ Editar socio (Editar → modal de edición)
  └─ Hacer clic fuera del modal
```

## 🖼️ Manejo de Imágenes

### Mostrar Foto
```javascript
fotoDocumento.src = '/corve/' + socio.documento_pdf;
fotoDocumento.style.display = 'block';
sinFoto.style.display = 'none';
```

### Mostrar Fallback
```javascript
fotoDocumento.style.display = 'none';
sinFoto.style.display = 'block';
```

**Nota**: El campo `documento_pdf` en BD guarda la ruta relativa dentro de `/corve/`

## ✅ Validaciones

- ✅ Si ID no es válido: error "No se encontraron datos del socio"
- ✅ Si foto no existe: muestra mensaje "No hay foto del documento"
- ✅ Formatos consistentes:
  - Fechas: formato local español
  - Moneda: $ con separadores de miles
  - Estados: Primera letra mayúscula

## 📊 Datos Manejados

El modal puede mostrar todos los datos del socio:
- Datos personales (nombre, documento, contacto, dirección)
- Datos de afiliación (fecha, estado, entidad de salud)
- Datos financieros (saldo/deuda)
- Foto de documento (si existe)
- Observaciones/notas

## 🔗 Dependencias

- **Bootstrap 5**: Modal API (`new bootstrap.Modal()`)
- **jQuery/DataTables**: Para manipulación de tabla
- **Font Awesome**: Iconos (ojo, editar)
- **Database**: API GET en socio_controller.php

## 📝 Ejemplos de Uso

### Abrir detalles de un socio específico (desde JavaScript)
```javascript
verSocioDetalles(5); // Abre detalles del socio ID 5
```

### API esperada (Respuesta GET)
```json
{
  "success": true,
  "data": {
    "id": 5,
    "nombre": "Juan García",
    "documento": "1234567890",
    "email": "juan@example.com",
    "telefono": "3001234567",
    "fecha_nacimiento": "1990-05-15",
    "direccion": "Calle 123 #45",
    "fecha_afiliacion": "2023-01-10",
    "estado": "activo",
    "entidad_salud": "EPS XYZ",
    "saldo": 0,
    "observaciones": "Socio activo sin deudas",
    "documento_pdf": "uploads/documentos/doc_1234567890_1765896725.jpg"
  }
}
```

## 🎨 Estilos

- Modal: 90 (responsive)
- Foto: máximo 300px de alto, redondeada
- Campos: lectura solamente (textarea deshabilitado)
- Botones: Bootstrap estándar (Cerrar=secondary, Editar=warning)

## ⚠️ Limitaciones Conocidas

- La foto solo se carga si existe en `documento_pdf`
- No hay edición directa desde el modal de ver
- No hay visualización de historial de cambios
- La foto debe estar en formato JPG/PNG/GIF

## 🚀 Próximos Pasos (Opcional)

- [ ] Agregar zoom a la foto del documento
- [ ] Mostrar historial de pagos
- [ ] Mostrar historial de cambios de estado
- [ ] Agregar descarga de documentos
- [ ] Vista de historial de observaciones

## 📂 Archivos Modificados

1. **socios.php**
   - Agregó: Modal verSocioModal (~170 líneas)
   - Agregó: Botón "Ver" en tabla de acciones

2. **public/js/socios.js**
   - Agregó: Función verSocioDetalles()
   - Agregó: Funciones auxiliares (formateo)
   - Agregó: Event listeners para btn-ver y btn-editar-modal
   - Líneas añadidas: ~110

## 🧪 Pruebas Realizadas

✅ Botón "Ver" aparece en tabla
✅ Clic en botón abre modal
✅ Datos se cargan correctamente
✅ Foto se muestra si existe
✅ Fallback funciona si no hay foto
✅ Botón "Editar" abre modal de edición
✅ Botón "Cerrar" cierra modal sin errores

## 📞 Soporte

En caso de errores:
1. Revisar consola del navegador (F12)
2. Verificar que foto existe en `/uploads/documentos/`
3. Verificar que socio_controller.php retorna JSON válido
4. Verificar permisos de lectura en directorio de uploads
