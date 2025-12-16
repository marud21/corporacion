# 🚀 Quick Reference - Sistema de Socios

## 🌐 Acceso Rápido

```
URL: http://localhost/corve/socios.php
Ctrl+Shift+R (limpiar caché)
```

---

## 📋 Operaciones Básicas

### Crear
```
1. Botón verde "+ Nuevo Socio"
2. Completa campos requeridos (*)
3. "Crear Socio"
```

### Editar
```
1. Botón amarillo (lápiz)
2. Modifica campos
3. "Guardar Cambios"
```

### Eliminar
```
1. Botón rojo (papelera)
2. Confirma
3. "OK"
```

### Filtrar
```
1. Ingresa búsqueda (nombre/documento)
2. Selecciona estado
3. "Filtrar" o Enter
```

---

## ✅ Errores Corregidos

| Error | Causa | Solución |
|-------|-------|----------|
| 400 | FormData | JSON + headers |
| 500 | fecha_baja | estado='retirado' |
| TypeError | Sin ID | id="btnGuardarCambios" |

---

## 📊 Campos Requeridos (*)

**Crear/Editar:**
- Nombre *
- Documento *
- Email *
- Teléfono *
- Fecha Afiliación * (crear)

---

## 🔍 Estados

- **Activo** 🟢 ($45k/mes)
- **Lesionado** 🟡 ($10k/mes)
- **Retirado** 🔴 ($0/mes)

---

## 💡 Tips

```
✓ Buscar en tiempo real (DataTable superior derecho)
✓ Ordenar por columna (click en encabezado)
✓ Cambiar registros por página (dropdown)
✓ Borrado lógico (datos no se pierden)
✓ Caché con timestamp (refresh siempre carga lo nuevo)
```

---

## 🎯 Métodos HTTP

| CRUD | Método | URL | Body |
|------|--------|-----|------|
| Create | POST | / | JSON |
| Read | GET | ?id=1 | - |
| Update | PUT | ?id=1 | JSON |
| Delete | DELETE | ?id=1 | - |

---

## 📂 Archivos Clave

```
socios.php                  → Vista HTML
public/js/socios.js         → Lógica
controllers/socio_controller.php → API
models/Socio.php            → BD
```

---

## ⚠️ Validaciones

```
✓ Campos requeridos
✓ Documento único
✓ Email válido
✓ HTTP status
✓ JSON válido
```

---

## 🔧 Troubleshooting

```
Error? → F12 → Console
No actualiza? → Ctrl+Shift+R
Modal no abre? → Recarga página
500 error? → Ver logs/php_error.log
```

---

## 📈 Vista de Datos

```
4 Tarjetas:
├─ Total
├─ Activos
├─ Lesionados
└─ Deuda Total

Tabla:
├─ Búsqueda DataTable
├─ Filtros
├─ Paginación
└─ Ordenamiento
```

---

**v2.2 | HTTP 200 | ✅ FUNCIONAL**
