# 📋 NUEVO SISTEMA DE MENSUALIDADES

## Descripción General

Se ha reescrito completamente el sistema de cobro de mensualidades. Ahora cada socio se le cobra de acuerdo a su **fecha de ingreso**, en el mismo día del mes cada mes.

---

## 🔄 Cambios Principales

### 1. **Cobro Basado en Aniversarios Mensuales**

#### Antes (Sistema Antiguo):
- Se cobraba a todos los socios el mismo día del mes (sin importar su fecha de ingreso)
- Se cobraba en el mes de ingreso

#### Ahora (Sistema Nuevo):
- Cada socio se le cobra en el **mismo día del mes en que ingresó**
- **No se cobra en el mes de ingreso** (primer mes es gratis)
- Si un socio ingresó el 31 y el mes actual tiene 30 días, se cobra el último día del mes
- Sistema automático basado en fechas

**Ejemplo:**
```
- Socio ingresa: 15 de junio de 2025
- Primer cobro: 15 de julio de 2025 (un mes después)
- Cobros posteriores: 15 de cada mes siguiente
```

### 2. **Archivos Modificados**

#### `controllers/cobro_mensual.php`
- **Cambios:**
  - Ahora inserta registros en la tabla `pagos` (anteriormente solo actualizaba saldo)
  - Calcula dinámicamente el día de cobro basado en fecha de ingreso
  - Maneja casos especiales (meses con diferentes días)
  - Verifica que el socio esté activo
  - Valida que exista fecha de ingreso
  - No cobra si es el mes de ingreso

- **Respuesta mejorada:**
```json
{
  "success": true,
  "cobrados": 5,
  "ya_cobrados": 2,
  "primer_mes": 1,
  "no_corresponde": 0,
  "sin_fecha_ingreso": 0,
  "monto": 45000,
  "fecha": "2025-12-15 10:30:45",
  "message": "Cobro mensual completado: 5 socios cobrados"
}
```

#### `logic/mensualidades.logic.php`
- **Cambios:**
  - Calcula próximos cobros para cada socio
  - Agrupa información de mensualidades por socio
  - Ordena socios por "días para próximo cobro"
  - Usa DateTime para manejo robusto de fechas
  - Calcula totales por socio y general

#### `views/socios/mensualidades.php`
- **Cambios:**
  - Nueva sección: "Próximos Cobros Programados"
  - Código de colores por proximidad:
    - 🔴 Rojo: Cobro en 3 días o menos
    - 🟠 Naranja: Cobro en 7 días o menos
    - 🔵 Azul: Cobro en más de 7 días
  - Tabla mejorada con información de ingreso
  - Muestra deudas actuales y pagos realizados
  - Mejor organización visual

#### `public/js/mensualidades.js`
- **Cambios:**
  - Modal mejorado con detalles del cobro
  - Muestra estadísticas de la ejecución
  - Mejor manejo de errores
  - Loading indicator durante el cobro

---

## 📊 Vista de Mensualidades - Nuevas Secciones

### 1. Resumen General
- Total pagado por todos los socios
- Cantidad de socios activos

### 2. Próximos Cobros Programados
Tabla ordenada por proximidad del cobro:
- Nombre del socio
- Documento
- Próximo cobro (fecha)
- Días para el cobro (con código de color)
- Estado del socio (Activo/Lesionado/Retirado)

### 3. Historial de Pagos
Últimos 50 pagos de mensualidades registrados

### 4. Resumen por Socio
Tabla completa con:
- ID y Nombre
- Documento
- Fecha de ingreso
- Próximo cobro
- Meses cobrados
- Total pagado
- Deuda actual
- Estado

---

## 🔐 Validaciones y Casos Especiales

### ✅ Validaciones Implementadas:

1. **Verificación de fecha de ingreso**
   - No cobra si el socio no tiene fecha de ingreso asignada

2. **Primer mes libre**
   - No cobra en el mes en que ingresó el socio

3. **Ya cobrado este mes**
   - Verifica si ya existe un pago de mensualidad para ese socio este mes

4. **Aniversario correcto**
   - Solo cobra si hoy coincide con el aniversario del ingreso
   - Ej: Ingresó día 15 → Cobra solo el día 15 de cada mes

5. **Socios activos solo**
   - No cobra a socios retirados o lesionados

6. **Manejo de meses con diferentes días**
   - Si ingresó día 31, cobra el último día del mes

---

## 🚀 Uso del Sistema

### Ejecutar Cobro Mensual desde la Web

1. Ve a: `http://localhost/corve/views/socios/mensualidades.php`
2. Haz clic en el botón verde: **"Ejecutar cobro mensual"**
3. Confirma dos veces (seguridad)
4. El sistema mostrará un resumen con:
   - Socios cobrados
   - Socios omitidos
   - Detalles de la ejecución

### Ejecutar desde Línea de Comandos

```bash
php /ruta/al/proyecto/controllers/cobro_mensual.php
```

El sistema solicitará dos confirmaciones (seguridad).

---

## 📈 Ejemplo de Flujo

```
┌─────────────────────────────────────────────┐
│ Día 15 de cada mes a las 23:59             │
├─────────────────────────────────────────────┤
│ Sistema verifica todos los socios activos   │
├─────────────────────────────────────────────┤
│ Para cada socio:                            │
│  1. ¿Tiene fecha de ingreso? → SÍ          │
│  2. ¿Es el mes de ingreso? → NO            │
│  3. ¿Ya se cobró este mes? → NO            │
│  4. ¿Es el aniversario? → SÍ (día 15)      │
├─────────────────────────────────────────────┤
│ Inserta pago en tabla 'pagos'              │
│ Suma 45,000 al saldo del socio             │
├─────────────────────────────────────────────┤
│ Registra en logs (logs/php_error.log)      │
└─────────────────────────────────────────────┘
```

---

## 🗄️ Estructura de Base de Datos

### Tabla `socios`
```sql
- id: INT
- fecha_afiliacion: DATE ← Columna crítica para el nuevo sistema
- estado: ENUM('activo', 'lesionado', 'retirado')
- saldo: FLOAT
```

### Tabla `pagos`
```sql
- id: INT
- socio_id: INT
- monto: FLOAT
- concepto: ENUM('afiliación', 'mensualidad', 'inscripción', 'sanción')
- fecha: DATETIME
- pagado: TINYINT(1)
```

---

## ⚠️ Consideraciones Importantes

### 1. **Fecha de Afiliación Obligatoria**
Para que un socio sea incluido en los cobros automáticos, **DEBE tener una fecha de ingreso/afiliación**.

### 2. **Primer Mes Gratuito**
El mes en que ingresa el socio NO se le cobra mensualidad.

### 3. **Estados del Socio**
- ✅ **Activo**: Se cobra normalmente
- ⚠️ **Lesionado**: No se cobra (pero mantiene deuda)
- ❌ **Retirado**: No se cobra

### 4. **Cambio de Día en Meses Especiales**
Si ingresó el 31 de enero, en febrero se cobrará el 28 (o 29 en bisiesto).

### 5. **Registros en BD**
Cada cobro genera:
- 1 registro en tabla `pagos`
- 1 actualización en `saldo` de tabla `socios`
- 1 entrada en log para auditoría

---

## 📋 Checklist de Implementación

- ✅ Reescrito `controllers/cobro_mensual.php`
- ✅ Actualizado `logic/mensualidades.logic.php`
- ✅ Rediseñada `views/socios/mensualidades.php`
- ✅ Mejorado `public/js/mensualidades.js`
- ✅ Documentación completada

---

## 🔍 Monitoreo y Auditoría

### Logs disponibles:
- `logs/php_error.log` - Registro detallado de cada cobro

### Información registrada:
- Fecha y hora de ejecución
- ID del socio cobrado
- Nombre del socio
- Monto cobrado
- Razones de omisión (si aplica)

---

## 📞 Contacto y Soporte

Si encuentras problemas o dudas sobre el nuevo sistema de mensualidades:
1. Revisa los logs en `logs/php_error.log`
2. Verifica que todos los socios tengan `fecha_afiliacion` configurada
3. Comprueba que haya socios con estado = 'activo'

---

**Versión:** 2.0 - Nuevo Sistema de Mensualidades
**Fecha:** 15 de Diciembre de 2025
