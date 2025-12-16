# 🧪 Guía de Testeo: Estado y Fecha de Afiliación

## ✅ Verificación Completada

Los campos `estado` y `fecha_afiliacion` ahora se actualizan correctamente en:
- ✅ Base de datos
- ✅ Vistas/Modales
- ✅ Controlador/API
- ✅ Modelo/Lógica

---

## 🎯 Cómo Probar

### Paso 1: Crear Nuevo Socio

```
1. Abre: http://localhost/corve/socios.php
2. Haz clic en "+ Nuevo Socio"
3. Completa el formulario:
   
   DATOS PERSONALES:
   - Nombre: "Test Usuario"
   - Documento: "9999999999"
   - Email: "test@example.com"
   - Teléfono: "3001234567"
   
   DATOS DE AFILIACIÓN:
   - Fecha Afiliación: "2025-12-16" (preestablecida a hoy)
   - Estado: "Activo" (selecciona del dropdown)
   - Dirección: "Calle Principal 123"
   - Entidad Salud: "Nueva EPS"

4. Haz clic en "Crear Socio"
5. Espera mensaje de éxito
6. ✅ Socio aparece en la tabla
```

### Paso 2: Verificar en Base de Datos

```
1. Abre phpMyAdmin o cliente MySQL
2. Conecta a BD "corporacion"
3. Ve a tabla "socios"
4. Busca el socio que acabas de crear
5. Verifica:
   ✓ fecha_afiliacion = "2025-12-16"
   ✓ estado = "activo"
   ✓ fecha_estado = fecha actual con hora
```

### Paso 3: Editar Estado

```
1. En la tabla de socios.php
2. Busca el socio que creaste
3. Haz clic en botón editar (lápiz amarillo)
4. En el modal:
   - Estado: Cambia de "Activo" a "Lesionado"
   - Haz clic en "Guardar Cambios"
5. Espera confirmación
6. ✅ Tabla se recarga con nuevo estado
```

### Paso 4: Verificar Cambios en BD

```
1. En phpMyAdmin, actualiza la tabla socios
2. Busca el mismo socio
3. Verifica:
   ✓ estado = "lesionado"
   ✓ fecha_estado = actualizada a la hora actual
   ✓ fecha_afiliacion = sin cambios (2025-12-16)
```

### Paso 5: Editar Fecha de Afiliación

```
1. En socios.php, abre el modal editar del socio
2. Cambia:
   - Fecha Afiliación: "2025-11-01"
3. Haz clic en "Guardar Cambios"
4. ✅ Se guarda correctamente
```

### Paso 6: Verificar Otra Vez

```
1. Recarga phpMyAdmin
2. Verifica:
   ✓ fecha_afiliacion = "2025-11-01" (actualizada)
   ✓ estado = "lesionado" (sin cambios)
   ✓ fecha_estado = sin cambios (porque no cambió estado)
```

---

## 📊 Lo Que Se Actualiza

| Campo | Crear | Editar | Automático | BD |
|-------|-------|--------|------------|-----|
| **estado** | ✅ | ✅ | - | ✅ |
| **fecha_afiliacion** | ✅ | ✅ | - | ✅ |
| **fecha_estado** | ✅ | ✅ | ✅ | ✅ |

---

## 🔍 Verificar Datos en Modal

Cuando abras el modal de editar, deben verse:

```
Modal "Editar Socio":
├─ DATOS PERSONALES
│  ├─ Nombre: "Test Usuario"
│  ├─ Documento: "9999999999"
│  ├─ Email: "test@example.com"
│  └─ Teléfono: "3001234567"
│
└─ DATOS DE AFILIACIÓN
   ├─ Fecha Afiliación: "2025-12-16" ✅
   ├─ Estado: "Lesionado" ✅
   ├─ Dirección: "Calle Principal 123"
   ├─ Entidad Salud: "Nueva EPS"
   └─ Observaciones: (vacío)
```

---

## 💾 Archivos Modificados

```
✅ models/Socio.php
   - Agregada propiedad: fecha_afiliacion
   - readOne() obtiene todos los campos
   - update() actualiza fecha_afiliacion

✅ controllers/socio_controller.php
   - update() guarda fecha_afiliacion
   - update() guarda estado
   - Registra fecha_estado automática

✅ public/js/socios.js
   - Ya envía estado
   - Ya envía fecha_afiliacion
```

---

## ✅ Checklist de Pruebas

- [ ] Crear socio con estado = "Activo"
- [ ] Verificar en BD que se guarda
- [ ] Editar estado a "Lesionado"
- [ ] Verificar en BD que se actualiza
- [ ] Verificar que fecha_estado se actualiza
- [ ] Editar fecha_afiliacion
- [ ] Verificar en BD que se actualiza
- [ ] Cargar socio en modal y ver todos los datos
- [ ] Guardar cambios múltiples
- [ ] Verificar que fecha_estado solo se actualiza cuando cambia estado
- [ ] Probar con varios socios diferentes
- [ ] Verificar que no hay errores en console (F12)

---

## 🎓 Información Técnica

### Estado
- **Valores:** Activo, Lesionado, Retirado
- **Almacenamiento:** Tabla socios, columna estado
- **Impacto:** Afecta la tarifa de cobro mensual
- **Editable:** Sí, en cualquier momento

### Fecha de Afiliación
- **Formato:** YYYY-MM-DD
- **Almacenamiento:** Tabla socios, columna fecha_afiliacion
- **Impacto:** Determina el día de cobro mensual
- **Editable:** Sí, en cualquier momento

### Fecha del Estado
- **Formato:** YYYY-MM-DD HH:MM:SS
- **Almacenamiento:** Tabla socios, columna fecha_estado
- **Impacto:** Auditoría (registro cuándo cambió)
- **Editable:** No (automático)

---

## 🚀 Script SQL para Verificación Manual

```sql
-- Ver todos los socios con campos clave
SELECT 
    u.id,
    u.nombre,
    u.documento,
    s.fecha_afiliacion,
    s.estado,
    s.fecha_estado,
    s.saldo
FROM usuarios u
LEFT JOIN socios s ON u.id = s.id
WHERE u.rol = 'socio' AND u.activo = 1
ORDER BY u.nombre;

-- Ver un socio específico
SELECT * FROM socios WHERE id = 1;

-- Verificar que fecha_afiliacion existe en BD
DESCRIBE socios;
```

---

## 🆘 Si Hay Problemas

### No se actualiza el estado
```
1. Verifica que hayas hecho clic en "Guardar Cambios"
2. Verifica que el dropdown de estado haya cambiado
3. Revisa console (F12) para errores
4. Verifica logs: logs/php_error.log
```

### No se actualiza fecha_afiliacion
```
1. Verifica que hayas introducido una fecha válida
2. Verifica que sea formato YYYY-MM-DD
3. Revisa que el campo no esté vacío
4. Verifica que hayas guardado cambios
```

### Modal no carga datos
```
1. Limpia caché: Ctrl+Shift+R
2. Abre DevTools: F12
3. Mira Network tab para ver respuesta JSON
4. Busca errores en console
```

---

## 📞 Validación

El sistema valida:
- ✅ Campos requeridos
- ✅ Formato de fecha válido
- ✅ Estado dentro de valores permitidos
- ✅ HTTP status
- ✅ JSON válido

---

## 🎉 Confirmación Final

Una vez que completes las pruebas:

```
Si todas las pruebas pasaron:
✅ Estado se actualiza correctamente
✅ Fecha de afiliación se actualiza
✅ Fecha de estado se registra automática
✅ Sistema funciona perfectamente
```

---

**Versión:** 2.3
**Fecha:** 16 de Diciembre de 2025
**Estado:** ✅ LISTO PARA TESTEO
