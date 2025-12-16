# 📖 Guía de Uso - Vista de Socios

## 🌐 Acceder a la Aplicación

```
URL: http://localhost/corve/socios.php
```

**Presiona:** `Ctrl+Shift+R` para limpiar caché

---

## 👥 Operaciones Disponibles

### 1️⃣ **Crear Nuevo Socio**

```
Pasos:
1. Haz clic en botón verde "+ Nuevo Socio"
2. Se abre modal con formulario
3. Completa TODOS los campos marcados con *
   - Nombre *
   - Documento *
   - Email *
   - Teléfono *
   - Fecha de Afiliación * (se preestablece a hoy)
   - Estado (activo por defecto)
4. Completa campos opcionales:
   - Fecha de Nacimiento
   - Dirección
   - Entidad de Salud
   - Observaciones
5. Haz clic en "Crear Socio"
6. Espera mensaje de éxito
7. Página se recarga automáticamente
```

**Resultado:** Nuevo socio aparece en la tabla

---

### 2️⃣ **Editar Socio Existente**

```
Pasos:
1. Busca el socio en la tabla (usar filtro si necesario)
2. Haz clic en botón amarillo (lápiz) 📝
3. Se abre modal con datos del socio
4. Realiza los cambios necesarios
5. Haz clic en "Guardar Cambios"
6. Espera confirmación
7. Página se recarga automáticamente
```

**Resultado:** Cambios guardados en la BD

---

### 3️⃣ **Eliminar Socio**

```
Pasos:
1. Busca el socio en la tabla
2. Haz clic en botón rojo (papelera) 🗑️
3. Se pide confirmación: "¿Estás seguro?"
4. Si aceptas:
   - Socio se marca como inactivo
   - Estado cambia a "retirado"
   - Desaparece de la tabla
5. Si cancelas: no se elimina nada
```

**Resultado:** Socio desaparece de vista (borrado lógico en BD)

---

### 4️⃣ **Filtrar Socios**

#### Por Nombre o Documento

```
1. Ingresa en campo "Buscar por nombre o documento"
2. Presiona Enter O haz clic en "Filtrar"
3. Tabla se actualiza mostrando resultados
```

#### Por Estado

```
1. Selecciona estado en dropdown:
   - Todos
   - Activo
   - Lesionado
   - Retirado (solo si existen)
2. Presiona "Filtrar"
3. Tabla muestra solo ese estado
```

#### Ambos Filtros

```
1. Ingresa búsqueda
2. Selecciona estado
3. Presiona "Filtrar"
4. Tabla muestra resultado de ambos filtros
```

---

### 5️⃣ **Usar DataTable**

La tabla tiene funcionalidades adicionales:

```
🔍 Buscar en tiempo real
   - Campo de búsqueda en esquina superior derecha

📄 Paginación
   - Selector de registros (5, 10, 25, 50, 100)
   - Navegación página anterior/siguiente

⬆️ Ordenamiento
   - Haz clic en encabezado de columna
   - Se ordena alfabético o numérico

📊 Información
   - Muestra total de registros mostrados
```

---

## 📊 Entender la Tabla

### Columnas

| Columna | Significado |
|---------|------------|
| **ID** | Identificador único del socio |
| **Nombre** | Nombre completo del socio |
| **Documento** | Cédula o número de documento |
| **Teléfono** | Número de contacto |
| **Estado** | Estado actual (Activo/Lesionado/Retirado) |
| **Saldo** | Deuda actual o "Al día" |
| **Acciones** | Botones para editar/eliminar |

### Estados

| Estado | Significado | Color | Cobro |
|--------|------------|-------|-------|
| **Activo** | Socio pagando completo | 🟢 Verde | $45,000/mes |
| **Lesionado** | Tarifa reducida | 🟡 Naranja | $10,000/mes |
| **Retirado** | No recibe cobro | 🔴 Rojo | $0 |

### Saldo

- **"Al día"** 🟢 = Sin deuda
- **"Debe: $XXX"** 🔴 = Tiene deuda

---

## 📈 Ver Estadísticas

### 4 Tarjetas de Resumen

1. **Total Socios** - Cuenta todos los socios activos
2. **Activos** - Socios pagando completo
3. **Lesionados** - Socios con tarifa reducida
4. **Deuda Total** - Suma de todas las deudas

Se actualizan automáticamente al crear/editar/eliminar.

---

## 🔧 Información Contextual

Al final de la página aparece:

```
ℹ️ Información:
• Activos: Socios pagando mensualidad completa ($45,000)
• Lesionados: Socios con tarifa reducida ($10,000)
• Retirados: Socios sin cobro hasta reactivarse
```

**Nota:** Los montos se cargan desde la sección de Tarifas.

---

## ⚠️ Validaciones

### Crear Socio

```
✅ Campo requerido → Marca con * rojo
❌ Sin llenar → Mensaje "Por favor completa todos los campos"
✅ Email válido → Se valida formato
✅ Documento único → Error si ya existe
```

### Editar Socio

```
✅ Campos requeridos → Deben completarse
❌ Sin cambios → Se actualiza igual
✅ Validación HTTP → Si hay error servidor, muestra
```

### Eliminar Socio

```
✅ Confirmación requerida → "¿Estás seguro?"
✅ Borrado lógico → No elimina físicamente
✅ Cambio de estado → Estado = "retirado"
```

---

## 🆘 Mensajes de Error Comunes

### Error 400 Bad Request

```
Causa: Datos incompletos o formato inválido
Solución: 
  1. Verifica todos los campos requeridos
  2. Recarga la página (Ctrl+F5)
  3. Intenta de nuevo
```

### Error 500 Internal Server

```
Causa: Problema en el servidor
Solución:
  1. Revisa el log: logs/php_error.log
  2. Contacta al administrador
  3. Intenta más tarde
```

### TypeError: Cannot read properties

```
Causa: Elemento no encontrado en HTML
Solución:
  1. Limpia caché (Ctrl+Shift+R)
  2. Recarga página
  3. Si persiste, contacta soporte
```

---

## 🎯 Flujo Típico de Trabajo

```
1. ACCEDER
   http://localhost/corve/socios.php
   ↓
2. VER LISTA
   DataTable muestra todos los socios
   ↓
3. FILTRAR (opcional)
   Buscar o seleccionar estado
   ↓
4. ACCIONES
   ├─ Crear: + Nuevo Socio
   ├─ Editar: Botón lápiz
   └─ Eliminar: Botón papelera
   ↓
5. ACTUALIZACIÓN
   Tabla se recarga automáticamente
```

---

## 💾 Datos Guardados

Cuando creates/editas un socio, se guarda:

```
📋 EN USUARIOS
  - nombre
  - documento (único)
  - email
  - telefono
  - direccion
  - fecha_nacimiento
  - activo (0/1)

📋 EN SOCIOS
  - fecha_afiliacion (determina día de cobro)
  - estado (activo/lesionado/retirado)
  - entidad_salud
  - saldo (deuda actual)
  - afiliado (0/1)
  - documento_pdf (ruta)
```

---

## 🔐 Privacidad y Datos

```
✅ Borrado lógico
   No se eliminan datos físicamente
   Solo se marca como inactivo
   Puede reactivarse si es necesario

✅ Historial
   Los cambios se registran con fecha
   Estado anterior se guarda

✅ Auditoría
   Se registra quién realiza cada acción
```

---

## 📞 Soporte

Si encuentras problemas:

1. **Revisa el log:** `logs/php_error.log`
2. **Abre DevTools:** F12 → Console
3. **Verifica Network:** F12 → Network Tab
4. **Contacta soporte** con screenshot del error

---

**Versión:** 1.0 - Guía de Uso
**Fecha:** 15 de Diciembre de 2025
**Estado:** ✅ Sistema Operativo
