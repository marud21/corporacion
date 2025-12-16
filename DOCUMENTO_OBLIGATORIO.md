# 📄 Validación: Foto de Documento Obligatoria para Nuevo Socio

**Fecha:** 16 de Diciembre de 2025  
**Versión:** 1.0  
**Estado:** ✅ IMPLEMENTADO

---

## 📝 Descripción de la Funcionalidad

Se ha agregado una validación **obligatoria** para que al registrar un nuevo socio, sea requerido subir la foto del documento de identidad de la persona.

### Cambios Implementados

#### 1. **Frontend (socios.php)**
- ✅ Agregado campo `Foto de Documento` al modal de nuevo socio
- ✅ Campo es obligatorio (marcado con asterisco rojo)
- ✅ Acepta solo imágenes (JPG, PNG, GIF)
- ✅ Muestra instrucciones: máximo 5MB

#### 2. **JavaScript (public/js/socios.js)**
- ✅ Modificada función `guardarNuevoSocio()` 
- ✅ Valida que el archivo exista
- ✅ Valida tipo MIME (imagen)
- ✅ Valida tamaño máximo (5MB)
- ✅ Envía datos como `FormData` en lugar de JSON
- ✅ Muestra error si falta el documento

#### 3. **Backend (controllers/socio_controller.php)**
- ✅ Nueva función `procesarFotoDocumento()` para:
  - Validar tipo MIME
  - Validar tamaño de archivo
  - Generar nombre único
  - Guardar en `/uploads/documentos/`
- ✅ Modificada función `store()` para:
  - Llamar a `procesarFotoDocumento()` de forma obligatoria
  - Guardar ruta en campo `documento_pdf` de BD

#### 4. **Base de Datos**
- ✅ Usa columna existente `documento_pdf` (VARCHAR 200)
- ✅ Almacena ruta relativa: `uploads/documentos/doc_*.jpg`

---

## 🔍 Cómo Funciona

### Flujo de Carga de Documento:

```
1. Usuario abre modal "Nuevo Socio"
   ↓
2. Completa todos los datos personales
   ↓
3. Selecciona una foto del documento (REQUERIDO)
   ↓
4. JavaScript valida:
   - ¿Archivo seleccionado? ✓
   - ¿Es imagen? ✓
   - ¿Menos de 5MB? ✓
   ↓
5. Envía FormData al servidor (incluye archivo)
   ↓
6. Servidor valida nuevamente:
   - MIME type válido ✓
   - Tamaño correcto ✓
   ↓
7. Genera nombre único: doc_DOCUMENTO_TIMESTAMP_ID.jpg
   ↓
8. Guarda en: /uploads/documentos/
   ↓
9. Almacena ruta en BD (documento_pdf)
   ↓
10. Socio creado ✅
```

---

## 📁 Estructura de Archivos

```
corve/
├── uploads/
│   └── documentos/          ← Nuevos archivos aquí
│       ├── .gitkeep
│       ├── doc_1234567_1702740000_507f.jpg
│       ├── doc_9876543_1702740015_8a9f.png
│       └── ...
├── public/
│   └── js/
│       └── socios.js        ← MODIFICADO
├── controllers/
│   └── socio_controller.php ← MODIFICADO
├── socios.php              ← MODIFICADO
└── models/
    └── Socio.php           ← Sin cambios (ya maneja documento_pdf)
```

---

## ✅ Validaciones Implementadas

### Cliente (JavaScript):

| Validación | Error | Acción |
|-----------|-------|--------|
| Archivo no seleccionado | "Debes seleccionar una foto" | Bloquea envío |
| Tipo de archivo inválido | "Solo JPG, PNG o GIF" | Bloquea envío |
| Archivo > 5MB | "No debe exceder 5MB" | Bloquea envío |
| Campos requeridos vacíos | "Completa todos los campos" | Bloquea envío |

### Servidor (PHP):

| Validación | Error | Acción |
|-----------|-------|--------|
| Archivo corrupto | "Error al cargar archivo" | HTTP 400 |
| MIME type inválido | "Tipo no permitido" | HTTP 400 |
| Archivo > 5MB | "Excede tamaño máximo" | HTTP 400 |
| No se puede guardar | "Error al guardar" | HTTP 500 |

---

## 🔐 Seguridad

1. **Validación MIME**: Usa `finfo_file()` para validar tipo MIME real (no confíable en cliente)
2. **Generación de nombre único**: Evita colisiones y sobreescrituras
3. **Límite de tamaño**: Protege contra uploads masivos
4. **Transacción BD**: Si algo falla, todo se revierte
5. **Permisos de carpeta**: `/uploads/documentos/` con permisos 777

---

## 📊 Ejemplo de Uso

### Crear Nuevo Socio con Documento:

```
1. Click en "+ Nuevo Socio"

2. Formulario Modal se abre

3. Ingresa datos:
   - Nombre: "Juan Pérez"
   - Documento: "1234567"
   - Email: "juan@example.com"
   - Teléfono: "3001234567"
   - Fecha de Afiliación: "2025-12-16"
   - Estado: "Activo"
   - Dirección: "Calle 1 #1"
   - Entidad Salud: "Nueva EPS"

4. IMPORTANTE: Selecciona foto documento
   - Busca archivo: cedula_frontal.jpg
   - Valida que sea imagen
   - Muestra nombre del archivo

5. Click en "Crear Socio"

6. Sistema procesa:
   ✓ Valida datos
   ✓ Valida documento
   ✓ Procesa imagen
   ✓ Guarda en BD
   ✓ Crea usuario

7. Resultado:
   ✅ "Socio creado correctamente"
```

---

## 🗂️ Datos Guardados en BD

```sql
INSERT INTO socios 
(id, entidad_salud, documento_pdf, afiliado, saldo, fecha_afiliacion)
VALUES 
(123, 'Nueva EPS', 'uploads/documentos/doc_1234567_1702740000_507f.jpg', 1, 190000, '2025-12-16')
```

**En campo `documento_pdf`:**
```
uploads/documentos/doc_1234567_1702740000_507f.jpg
                   ▲      ▲      ▲        ▲     ▲
                   |      |      |        |     |
              Prefijo  Documento Timestamp ID  Extensión
```

---

## 🔧 Función Auxiliar: `procesarFotoDocumento()`

```php
/**
 * Procesa carga de archivo de documento
 * @param string $documento Número de documento del socio
 * @return string Ruta del archivo guardado
 * @throws Exception Si hay error
 */
function procesarFotoDocumento($documento) {
    // 1. Valida que exista $_FILES['foto_documento']
    // 2. Valida MIME type con finfo_file()
    // 3. Valida tamaño (máx 5MB)
    // 4. Crea directorio si no existe
    // 5. Genera nombre único
    // 6. Mueve archivo a uploads/documentos/
    // 7. Retorna ruta relativa para guardar en BD
}
```

---

## ⚠️ Errores Comunes y Soluciones

### "Error: El archivo no existe"
- **Causa:** Campo `foto_documento` no enviado
- **Solución:** Verifica que el input file tenga `name="foto_documento"`

### "Error: Tipo de archivo no permitido"
- **Causa:** Intentaste subir un PDF, DOC, etc.
- **Solución:** Solo se permiten imágenes (JPG, PNG, GIF)

### "Error: El archivo excede 5MB"
- **Causa:** Imagen muy pesada
- **Solución:** Comprime la imagen o selecciona una más pequeña

### "Error: No se pudo guardar el archivo"
- **Causa:** Permisos insuficientes en carpeta uploads
- **Solución:** Ejecuta: `chmod 777 uploads/documentos/`

---

## 🧪 Pruebas Realizadas

✅ Crear socio sin documento → Error  
✅ Crear socio con documento JPG → Éxito  
✅ Crear socio con documento PNG → Éxito  
✅ Crear socio con archivo > 5MB → Error  
✅ Crear socio con PDF → Error  
✅ Verificar que archivo se guardó en carpeta  
✅ Verificar que ruta se guardó en BD  

---

## 📋 Checklist de Implementación

- [x] Campo `foto_documento` agregado al modal HTML
- [x] Validación HTML5 (accept=image/*, required)
- [x] Función JavaScript para validar archivo
- [x] Envío de FormData en lugar de JSON
- [x] Directorio `/uploads/documentos/` creado
- [x] Función `procesarFotoDocumento()` implementada
- [x] Validación de tipo MIME en servidor
- [x] Validación de tamaño en servidor
- [x] Generación de nombre único
- [x] Almacenamiento en BD
- [x] Documentación completa

---

## 🔄 Próximas Mejoras (Opcionales)

- [ ] Agregar preview de imagen antes de subir
- [ ] Permitir editar documento en vista editar socio
- [ ] Crear galería/visor de documentos
- [ ] Agregar validación OCR para extraer datos
- [ ] Comprimir imagen automáticamente
- [ ] Crear backup automático de documentos

---

## 📞 Soporte

Si encuentras problemas:

1. Verifica que la carpeta `/uploads/documentos/` tenga permisos 777
2. Revisa `logs/php_error.log` para errores del servidor
3. Abre DevTools (F12) y mira la consola de JavaScript
4. Verifica que el archivo sea una imagen válida

---

**Estado:** ✅ LISTO PARA PRODUCCIÓN

Versión 1.0 | 16 de Diciembre de 2025
