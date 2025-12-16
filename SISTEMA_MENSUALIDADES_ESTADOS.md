# 🔄 Sistema de Mensualidades - Actualización de Estados

## ✨ Cambios Realizados

Se ha actualizado el sistema de cobro de mensualidades para considerar los diferentes estados de los socios:

### **Estados y Cobros:**

| Estado | Mensualidad | Descripción |
|--------|-------------|-------------|
| **Activo** | $45,000 | Cobro mensual completo |
| **Lesionado** | $10,000 | Cobro reducido durante lesión |
| **Retirado** | $0 | Sin cobro hasta reactivarse |

---

## 🎯 Lógica Implementada

### 1. **Socios ACTIVOS** ✅
- Se cobran **$45,000** mensuales
- Se cobra en el mismo día del mes que ingresaron
- No se cobra en el mes de ingreso (primera mensualidad gratis)

### 2. **Socios LESIONADOS** 🏥
- Se cobran **$10,000** mensuales (tarifa reducida)
- Se cobra en el mismo día del mes que ingresaron
- La tarifa reducida aplica solo mientras estén en estado "lesionado"
- Una vez cambien a "activo", vuelven a cobrar $45,000

### 3. **Socios RETIRADOS** ❌
- **No se cobran** mientras estén retirados
- Si se reactivan a "activo" o "lesionado", vuelven a recibir cobro
- Se pueden reactivar en cualquier momento

---

## 📋 Ejemplos

### Ejemplo 1: Socio que pasa de Activo a Lesionado
```
Enero:  ACTIVO      → Cobro de $45,000
Febrero: LESIONADO  → Cobro de $10,000 (reducido)
Marzo:  LESIONADO   → Cobro de $10,000
Abril:  ACTIVO      → Cobro de $45,000 (vuelve a completo)
```

### Ejemplo 2: Socio Retirado
```
Enero:  ACTIVO      → Cobro de $45,000
Febrero: RETIRADO   → Sin cobro
Marzo:  RETIRADO    → Sin cobro
Abril:  ACTIVO      → Cobro de $45,000 (se reactiva)
```

---

## 🔧 Cambios Técnicos en `cobro_mensual.php`

### Antes:
```php
// Solo cobraba a socios "activo"
WHERE s.estado = 'activo'

// Monto fijo de $45,000
$mensualidad = 45000;
```

### Ahora:
```php
// Cobra a socios "activo" Y "lesionado"
WHERE s.estado IN ('activo', 'lesionado')

// Montos diferentes según estado
$mensualidad = 45000;           // Para activos
$mensualidad_lesionado = 10000; // Para lesionados

// En el bucle, se determina el monto según estado
$monto = ($estadoSocio === 'lesionado') ? $mensualidad_lesionado : $mensualidad;
```

---

## 📊 Cálculo de Ejemplo

**Socio Lesionado durante 3 meses:**
```
Enero:  $10,000 (LESIONADO)
Febrero: $10,000 (LESIONADO)
Marzo:   $10,000 (LESIONADO)
─────────────────
Total:   $30,000 (vs $45,000 si estuviera activo)
```

**Socio Retirado durante 2 meses:**
```
Enero:  $0 (RETIRADO)
Febrero: $0 (RETIRADO)
─────────────────
Total:   $0
```

---

## 🚀 Cómo Cambiar el Estado de un Socio

1. Ve a **Socios** → Edita el socio
2. Cambia el estado:
   - **Activo** → Cobro completo ($45,000)
   - **Lesionado** → Cobro reducido ($10,000)
   - **Retirado** → Sin cobro ($0)
3. Guarda los cambios

El próximo cobro automático respetará el nuevo estado.

---

## 📝 Montos Configurables

Si necesitas cambiar los montos, edita `controllers/cobro_mensual.php` línea ~66:

```php
$mensualidad = 45000;           // Cambiar aquí si es necesario
$mensualidad_lesionado = 10000; // Cambiar aquí si es necesario
```

---

## 🧪 Testing

Para probar el cobro mensual:

### CLI (Más seguro):
```bash
php controllers/cobro_mensual.php
```

### API HTTP POST:
```bash
curl -X POST http://localhost/corve/controllers/cobro_mensual.php
```

Ambos mostrarán un resumen con:
- ✓ Socios cobrados
- ⚠ Socios ya cobrados este mes
- ⚠ Socios en primer mes
- ⚠ Socios lesionados cobrados
- 📝 Socios retirados (no cobrados)

---

## 🔐 Registros en Log

Todos los cobros se registran en `logs/php_error.log` con:
- ID del socio
- Nombre del socio
- Estado (ACTIVO o LESIONADO)
- Monto cobrado
- Fecha/hora

Ejemplo:
```
✓ Cobro aplicado a socio 5 (Juan Pérez) [LESIONADO] - Monto: $10.000
```

---

## 📈 Validaciones

El sistema valida:
- ✅ Que el socio tenga fecha de ingreso
- ✅ Que sea su aniversario mensual (día de ingreso)
- ✅ Que no haya sido cobrado dos veces en el mismo mes
- ✅ Que no se cobre en el mes de ingreso
- ✅ Que el estado sea "activo" o "lesionado" (no "retirado")

---

## ⚠️ Importante

- Los socios **RETIRADOS** no reciben cobro automático
- Para reactivar un socio, cambia su estado a "Activo" o "Lesionado"
- El cambio de estado **toma efecto inmediatamente** en el próximo cobro
- Los cobros pasados se conservan en el historial

---

**Versión:** 2.1 - Sistema de Mensualidades con Estados
**Fecha:** 15 de Diciembre de 2025
**Estado:** ✅ Completado
