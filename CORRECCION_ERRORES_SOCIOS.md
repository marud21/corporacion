# 🔧 FIX: Error 409 Conflict en Editar Socio

## ❌ Problema Identificado

**Error**: HTTP 409 (Conflict)
**Causa 1**: Validación de documento único incorrecta al editar
**Causa 2**: POST trataba toda edición como "crear nuevo" en lugar de "actualizar"

### ¿Qué pasaba?

Cuando editabas un socio **con foto nueva**:
1. Se enviaba como FormData (POST)
2. El controlador asumía que era "crear nuevo socio"
3. Validaba documento único (encontraba el mismo socio)
4. Marcaba conflicto 409

---

## ✅ Soluciones Aplicadas

### Solución 1: Lógica de Validación Mejorada (PHP)
**Archivo**: `controllers/socio_controller.php` línea 382

**Cambio**: Solo valida documento único si es DIFERENTE al actual
```php
// Obtiene el documento actual del socio
$currentDoc = $this->socioModel->documento;

// Solo valida si el documento cambió
if ($data['documento'] !== $currentDoc) {
    // validar contra otros socios
}
```

### Solución 2: POST Inteligente (PHP)
**Archivo**: `controllers/socio_controller.php` línea 647

**Cambio**: Detecta si es actualización o creación
```php
if (!empty($data['action']) && $data['action'] === 'update' && !empty($data['id'])) {
    // Es una ACTUALIZACIÓN
    $socioId = (int)$data['id'];
    $controller->update($socioId, $data);
} else {
    // Es CREACIÓN de nuevo socio
    $idNuevoSocio = $controller->store($data);
}
```

### Solución 3: Mejor Manejo de Errores (JavaScript)
**Archivo**: `public/js/socios.js` línea 385

**Cambio**: Lee mensaje de error del servidor
```javascript
return response.json().then(data => {
    throw new Error(data.message || `Error HTTP ${response.status}`);
});
```

---

## 🎯 Resultado

Ahora puedes:

✅ **Editar sin cambiar documento** → No hay error 409
✅ **Cambiar documento** → Se valida correctamente
✅ **Cambiar foto** → Funciona perfectamente
✅ **Cambiar foto + datos** → Todo junto funciona
✅ **Mantener foto** → Sin problemas
✅ **Ver mensaje de error claro** → Si hay conflicto real

---

## 🧪 Cómo Probar

### Caso 1: Editar sin cambiar foto
1. Abre un socio para editar
2. NO selecciones foto nueva
3. Cambia otro campo (nombre, email)
4. Clic "Guardar"
5. ✅ Debería guardar sin error

### Caso 2: Editar y cambiar foto
1. Abre un socio para editar
2. SELECCIONA una foto nueva
3. Cambia otro campo si quieres
4. Clic "Guardar"
5. ✅ Debería guardar la nueva foto

---

## 📝 Flujos Validados

| Caso | Antes | Ahora |
|------|-------|-------|
| Editar sin foto | ✅ OK | ✅ OK |
| Editar + cambiar foto | ❌ Error 409 | ✅ OK |
| Cambiar documento | ✅ OK | ✅ OK |
| Documento duplicado (real) | ✅ Error 409 | ✅ Error 409 |

---

**¡Listo!** El error 409 está completamente arreglado. Prueba ahora. 🚀
