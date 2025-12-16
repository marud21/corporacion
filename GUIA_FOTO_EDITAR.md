# 📸 NUEVO: Opción de Foto en Editar

## ✅ Lo que se agregó

### En el Modal de Editar:
1. **Vista previa de la foto actual** (lado izquierdo)
2. **Input para cambiar la foto** (lado derecho, opcional)
3. **Información**: "Dejar en blanco mantiene la foto actual"

### Comportamiento:
```
✅ Si cambias la foto → Se guarda la nueva
✅ Si dejas en blanco → Se mantiene la foto actual
✅ Si no hay foto actual → Puedes agregar una
```

---

## 🎯 Cómo Usar

1. **Abre modal de Editar** (botón naranja con lápiz)
2. **Verá la foto actual** en el preview
3. **Opcionalmente**: selecciona nueva foto
4. **Edita otros datos** como nombre, email, etc.
5. **Clic en "Guardar Cambios"**
6. ✅ **Listo** - todo se guarda correctamente

---

## 🔧 Cambios Técnicos

### socios.php
- Agregó `enctype="multipart/form-data"` al formulario
- Agregó sección con preview + input file (~30 líneas)

### public/js/socios.js
- `cargarSocioParaEditar()`: Carga foto actual en preview
- `guardarCambiosSocio()`: Detecta si hay foto nueva o no
- Listener para preview en tiempo real

### controllers/socio_controller.php
- `procesarFotoDocumento()`: Ahora tolera falta de archivo en actualización
- Caso PUT: Ahora maneja FormData + JSON
- `update()`: Solo actualiza foto si viene en los datos

---

## 🎉 Resultado Final

✅ **Ahora puedes:**
- Ver la foto actual al editar
- Cambiar la foto si quieres
- Mantener la foto si NO cambias
- Todo desde un solo modal

✅ **Sin riesgos:**
- La foto no se borra accidentalmente
- Solo se actualiza si realmente cambias
- Validaciones en cliente y servidor
- Preview antes de guardar

---

**¡Listo para usar!** 🚀
