# 🔧 Corrección: Error 400 en Crear Socio con Foto

**Fecha:** 16 de Diciembre de 2025  
**Problema:** HTTP 400 al crear socio con foto de documento  
**Causa:** Conflicto entre FormData y JSON  
**Estado:** ✅ SOLUCIONADO

---

## 🔴 Problema Reportado

```
Failed to load resource: the server responded with a status of 400 (Bad Request)
Exception: No se recibieron datos para crear el socio
```

---

## 🔍 Causa Raíz

El controlador (`socio_controller.php`) esperaba recibir datos en **JSON**:

```php
// ❌ SOLO ACEPTABA JSON
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);
```

Pero el nuevo JavaScript enviaba datos como **FormData** (porque incluía archivo):

```javascript
// ✅ ENVIABA FormData
const formData = new FormData();
formData.append('nombre', ...);
formData.append('foto_documento', archivo);
fetch(url, { method: 'POST', body: formData })
```

**Resultado:** El servidor no encontraba los datos JSON → HTTP 400

---

## ✅ Solución Implementada

Se modificó `socio_controller.php` (caso POST) para aceptar **ambos tipos de datos**:

```php
// ✅ AHORA ACEPTA AMBOS

// Detectar tipo de contenido
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'multipart/form-data') !== false) {
    // Es FormData (con archivo)
    $data = $_POST;  // ← Lee de $_POST
    error_log('[DEBUG] FormData');
} else {
    // Es JSON (sin archivo)
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);  // ← Lee de php://input
    error_log('[DEBUG] JSON');
}
```

---

## 🎯 Cambio Técnico

| Aspecto | Antes | Después |
|--------|-------|---------|
| **Acepta JSON** | ✅ Sí | ✅ Sí |
| **Acepta FormData** | ❌ No | ✅ Sí |
| **Detecta tipo** | ❌ No | ✅ Automático |
| **HTTP 400** | ❌ Sí (FormData) | ✅ No |

---

## 📊 Flujo de Datos

### Caso 1: Crear sin archivo (JSON)
```
JavaScript: JSON.stringify({...})
        ↓
Header: Content-Type: application/json
        ↓
Servidor: Lee de php://input
        ↓
Procesa JSON
        ↓
HTTP 201 ✅
```

### Caso 2: Crear con archivo (FormData)
```
JavaScript: FormData
        ↓
Header: Content-Type: multipart/form-data
        ↓
Servidor: Lee de $_POST
        ↓
Procesa FormData + archivo
        ↓
HTTP 201 ✅
```

---

## ✅ Verificación

```
✅ FormData se envía correctamente
✅ Servidor detecta tipo
✅ Datos se reciben en $_POST
✅ Archivo se procesa
✅ Datos se guardan en BD
✅ HTTP 201 (éxito)
```

---

## 🚀 Ahora Funciona

1. Abre `http://localhost/corve/socios.php`
2. Haz clic en **"+ Nuevo Socio"**
3. Completa el formulario
4. **Selecciona una foto del documento**
5. Haz clic en **"Crear Socio"**
6. ✅ Debe mostrar: **"Socio creado correctamente"**

---

## 🔧 Archivos Modificados

**Archivo:** `controllers/socio_controller.php`

```diff
- $jsonInput = file_get_contents('php://input');
- if (empty($jsonInput)) {
-     throw new Exception('No se recibieron datos para crear el socio', 400);
- }
- $data = json_decode($jsonInput, true);

+ $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
+ 
+ if (strpos($contentType, 'multipart/form-data') !== false) {
+     $data = $_POST;
+ } else {
+     $jsonInput = file_get_contents('php://input');
+     if (empty($jsonInput)) {
+         throw new Exception('No se recibieron datos', 400);
+     }
+     $data = json_decode($jsonInput, true);
+ }
```

---

## 📋 Por Qué Esto Funciona

### Content-Type Headers

```
JSON Request:
Content-Type: application/json
              ↓
Datos en: php://input
         $_POST vacío

FormData Request:
Content-Type: multipart/form-data; boundary=----...
              ↓
Datos en: $_POST
         php://input vacío
```

### Detección

```php
if (strpos($contentType, 'multipart/form-data') !== false) {
    // Es FormData → usa $_POST
} else {
    // Es JSON → usa php://input
}
```

---

## 🎓 Lecciones Aprendidas

### FormData es necesario para archivos
```javascript
// ❌ NO FUNCIONA (JSON)
body: JSON.stringify({ archivo: fileData })

// ✅ FUNCIONA (FormData)
const fd = new FormData();
fd.append('archivo', fileInput.files[0]);
```

### Headers determinan cómo leer datos
```php
// Depende del Content-Type
$contentType = $_SERVER['CONTENT_TYPE'];

if (strpos($contentType, 'multipart/form-data') !== false) {
    // Lee de $_FILES y $_POST
} elseif (strpos($contentType, 'application/json') !== false) {
    // Lee de php://input
}
```

---

## ✨ Resultado Final

```
✅ HTTP 400 SOLUCIONADO
✅ FormData se envía correctamente
✅ Archivo se recibe correctamente
✅ Socio se crea correctamente
✅ Foto se guarda en BD
```

---

**Estado:** ✅ LISTO PARA USAR

Versión 1.0 | 16 de Diciembre de 2025
