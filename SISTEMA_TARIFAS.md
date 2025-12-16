# 💰 Sistema de Tarifas - Documentación Completa

## ✨ Descripción General

Se ha implementado un sistema centralizado de tarifas que permite configurar y gestionar todos los montos del sistema de forma fácil y flexible:

- ✅ **Mensualidad Activo** - Cobro completo para socios activos
- ✅ **Mensualidad Lesionado** - Cobro reducido para socios lesionados
- ✅ **Afiliación** - Cuota inicial de ingreso
- ✅ **Inscripción** - Tarifa administrativa

---

## 🎯 Características Principales

### 1. **Interfaz Web Intuitiva**
- Acceso desde: `http://localhost/corve/views/admin/tarifas.php`
- Visualización de tarifas en tarjetas de resumen
- Tabla completa de todas las tarifas
- Edición en tiempo real sin necesidad de recargar

### 2. **Tarifas Dinámicas**
Todas las tarifas se obtienen de la BD, no están hardcodeadas:
- ✅ `cobro_mensual.php` - Obtiene tarifas de BD
- ✅ `pago_controller.php` - Puede usar tarifas configurables
- ✅ Nuevos socios - Usarán tarifa de afiliación actual

### 3. **Auditoría**
Cada tarifa tiene:
- ID única
- Concepto (no se puede cambiar)
- Monto (editable)
- Descripción (editable)
- Estado activa/inactiva
- Fecha de creación y actualización

---

## 📋 Tarifas Incluidas

| Concepto | Monto Defecto | Descripción | Uso |
|----------|---------------|-------------|-----|
| **Mensualidad Activo** | $45,000 | Cobro mensual completo | Socios activos |
| **Mensualidad Lesionado** | $10,000 | Cobro reducido | Socios lesionados |
| **Afiliación** | $100,000 | Cuota inicial | Nuevos socios |
| **Inscripción** | $0 | Tarifa administrativa | Registro |

---

## 🚀 Cómo Usar

### Acceder a Tarifas

1. Desde el **sidebar**, haz clic en **"Tarifas"** (nueva sección)
2. O ve directamente a: `http://localhost/corve/views/admin/tarifas.php`

### Editar una Tarifa

1. Haz clic en el botón **Editar** (<i class="fas fa-edit"></i>) de la tarifa
2. Se abrirá un modal con:
   - Concepto (no editable)
   - Monto (editable)
   - Descripción (opcional)
   - Estado (activa/inactiva)
3. Haz cambios y guarda
4. Los cambios se aplican **inmediatamente**

### Ejemplo: Cambiar Mensualidad de Activos

**Antes:**
- Mensualidad Activo: $45,000

**Cambio:**
1. Abre Tarifas
2. Edita "Mensualidad Activo"
3. Cambia a $50,000
4. Guarda

**Resultado:**
- Próximo cobro: $50,000 por socio

---

## 🔧 Estructura Técnica

### Base de Datos
```sql
CREATE TABLE tarifas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    concepto VARCHAR(100) NOT NULL UNIQUE,
    monto DECIMAL(10, 2) NOT NULL DEFAULT 0,
    descripcion TEXT,
    activa BOOLEAN DEFAULT 1,
    fecha_creacion TIMESTAMP,
    fecha_actualizacion TIMESTAMP
);
```

### Archivos Creados

1. **`models/Tarifa.php`** - Modelo CRUD
   - `getAll()` - Obtiene todas las tarifas
   - `getActivas()` - Obtiene tarifas activas
   - `getByConcepto($concepto)` - Busca por concepto
   - `getMonto($concepto)` - Obtiene monto de una tarifa
   - `update()` - Actualiza una tarifa
   - `getResumen()` - Resumen de tarifas activas

2. **`controllers/tarifa_controller.php`** - API REST
   - GET `/tarifa_controller.php` - Obtiene todas
   - GET `/tarifa_controller.php?action=resumen` - Resumen
   - POST - Actualiza tarifa

3. **`views/admin/tarifas.php`** - Interfaz web
   - Tarjetas de resumen
   - Tabla de tarifas
   - Modal de edición
   - Ayuda integrada

4. **`public/js/tarifas.js`** - Lógica de cliente
   - Carga de tarifas
   - Edición modal
   - Guardado AJAX
   - Recarga automática

### Integración Existente

✅ `controllers/cobro_mensual.php` - Ahora obtiene tarifas de BD:
```php
$tarifaModel = new Tarifa($db);
$mensualidad = $tarifaModel->getMonto('Mensualidad Activo');
$mensualidad_lesionado = $tarifaModel->getMonto('Mensualidad Lesionado');
```

---

## 📊 Ejemplos de Uso

### Obtener Monto de una Tarifa (PHP)
```php
require_once 'models/Tarifa.php';
$db = (new Database())->getConnection();
$tarifaModel = new Tarifa($db);

// Opción 1: Obtener monto directo
$monto = $tarifaModel->getMonto('Mensualidad Activo');
// Resultado: 45000

// Opción 2: Obtener tarifa completa
$tarifa = $tarifaModel->getByConcepto('Afiliación');
// Resultado: [
//   'id' => 4,
//   'concepto' => 'Afiliación',
//   'monto' => 100000,
//   'descripcion' => 'Cuota de afiliación',
//   'activa' => 1
// ]

// Opción 3: Resumen completo
$resumen = $tarifaModel->getResumen();
// Resultado: [
//   'Mensualidad Activo' => 45000,
//   'Mensualidad Lesionado' => 10000,
//   'Afiliación' => 100000,
//   'Inscripción' => 0
// ]
```

### Obtener Tarifas (JavaScript)
```javascript
// Obtener resumen de tarifas
fetch('/corve/controllers/tarifa_controller.php?action=resumen')
    .then(r => r.json())
    .then(data => {
        console.log('Mensualidad Activo:', data.data['Mensualidad Activo']);
        console.log('Mensualidad Lesionado:', data.data['Mensualidad Lesionado']);
    });

// Obtener todas las tarifas
fetch('/corve/controllers/tarifa_controller.php')
    .then(r => r.json())
    .then(data => {
        console.log('Todas las tarifas:', data.data);
    });
```

---

## ⚙️ Configuración

### Cambiar Montos por Defecto

Edita la tabla directamente vía SQL:
```sql
UPDATE tarifas SET monto = 50000 WHERE concepto = 'Mensualidad Activo';
UPDATE tarifas SET monto = 12000 WHERE concepto = 'Mensualidad Lesionado';
UPDATE tarifas SET monto = 120000 WHERE concepto = 'Afiliación';
UPDATE tarifas SET monto = 5000 WHERE concepto = 'Inscripción';
```

O usa la interfaz web (recomendado).

### Agregar Nueva Tarifa

Para agregar un nuevo concepto:
```sql
INSERT INTO tarifas (concepto, monto, descripcion, activa) 
VALUES ('Nuevo Concepto', 25000, 'Descripción', 1);
```

Luego úsalo en el código:
```php
$monto = $tarifaModel->getMonto('Nuevo Concepto');
```

---

## 🔒 Consideraciones de Seguridad

✅ Validación de entrada en controlador
✅ Manejo de errores con try-catch
✅ Transacciones DB para consistencia
✅ Logging de cambios en error.log
✅ Protección contra inyección SQL con prepared statements

---

## 📈 Auditoría y Logs

Todos los cambios se registran automáticamente:
```
fecha_creacion: 2025-12-15 10:30:00
fecha_actualizacion: 2025-12-16 14:45:00 (cuando se edita)
```

---

## 🧪 Testing

### Probar desde Terminal
```bash
# Obtener todas las tarifas
curl http://localhost/corve/controllers/tarifa_controller.php

# Obtener resumen
curl "http://localhost/corve/controllers/tarifa_controller.php?action=resumen"

# Actualizar una tarifa (POST)
curl -X POST http://localhost/corve/controllers/tarifa_controller.php \
  -H "Content-Type: application/json" \
  -d '{"id": 1, "monto": 50000, "descripcion": "Nueva descripción", "activa": 1}'
```

---

## 📝 Changelog

### v1.0 (15 Diciembre 2025)
- ✅ Creación de tabla `tarifas`
- ✅ Modelo Tarifa con CRUD
- ✅ Controlador API REST
- ✅ Interfaz web intuitiva
- ✅ Integración en `cobro_mensual.php`
- ✅ JavaScript para operaciones AJAX

---

## 🎯 Próximas Mejoras Sugeridas

- [ ] Historial de cambios de tarifas
- [ ] Gráficos de tendencia de montos
- [ ] Múltiples escenarios de tarifas
- [ ] Descuentos por antigüedad
- [ ] Promociones temporales

---

**Versión:** 1.0 - Sistema de Tarifas
**Fecha:** 15 de Diciembre de 2025
**Estado:** ✅ Completado y Funcionando
