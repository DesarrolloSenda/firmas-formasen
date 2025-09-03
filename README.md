# 🖊️ Plugin Local Sendafirmas para Moodle

[![Moodle](https://img.shields.io/badge/Moodle-4.0+-blue.svg)](https://moodle.org/)
[![License](https://img.shields.io/badge/License-GPL%20v3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net/)


## 📌 Descripción

`local_sendafirmas` es un plugin para Moodle que permite **recoger y gestionar firmas digitales de los alumnos** en un curso, y posteriormente insertarlas en los certificados generados por módulos como **Simple Certificate** o **Custom Certificate**.

### ¿Qué hace este plugin?

- ✅ Los alumnos o responsables pueden **firmar en un formulario online** mediante un panel de dibujo (canvas)
- ✅ La firma se convierte automáticamente en **imagen JPEG compatible con TCPDF**
- ✅ Se guarda en el **sistema de archivos de Moodle** (File API) bajo el contexto del usuario
- ✅ La URL de la firma se almacena en un **campo de perfil personalizado** para poder reutilizarla en certificados o reportes

## 🚀 Funcionalidades principales

- 🎯 **Selector de curso y grupo** para listar alumnos
- 🔍 **Buscador de alumnos** por nombre dentro del grupo
- 🖱️ **Panel de firma** con soporte para ratón y pantallas táctiles
- 💾 **Guardado seguro** de la firma con conversión automática a JPG baseline
- 🔗 **Generación de URL pública** con token HMAC para que TCPDF pueda insertar la firma en el PDF
- 🎓 **Integración con plantillas** de certificado usando el campo de perfil `{PROFILE_FIRMA}`

## ✅ Requisitos

- 📚 **Moodle 4.0** o superior
- 🐘 **PHP 7.4+** con extensión **GD** habilitada (para manipulación de imágenes)
- 👤 **Permisos** para modificar campos de perfil de usuario
- 🎨 Módulo de certificados como **Simple Certificate** o **Custom Certificate** (opcional)

## ⚙️ Instalación

# Opción 1: Clonar desde GitHub
git clone https://github.com/tuusuario/local_sendafirmas local/sendafirmas

# Opción 2: Descargar ZIP y extraer en local/sendafirmas/

### 2. Instalar en Moodle

1. Acceder a Moodle como **administrador**
2. Ir a **Administración del sitio** > **Notificaciones**
3. Seguir el proceso de instalación del plugin

### 3. Configurar campo de perfil

Crear un campo de perfil de usuario:
- **Shortname**: `firma`
- **Tipo**: Texto
- Este campo almacenará la URL pública de cada firma

## 🔧 Configuración

Una vez instalado, el plugin estará disponible en:

\`\`\`
https://tusitio.com/local/sendafirmas/index.php?courseid=ID_DEL_CURSO
\`\`\`

## 🖊️ Uso

### Paso a paso

1. **Acceder al formulario** de firmas desde el enlace del curso
2. **Seleccionar el grupo** del curso
3. **Buscar un alumno** y pulsar **"Firmar"**
4. El alumno **dibuja su firma** en el panel táctil
5. **Guardar la firma** - se almacena como `firma_USERID.jpg`

### Integración en certificados

En las plantillas de certificado, usar:

**img src="{PROFILE_FIRMA}" alt="Firma del alumno" height="80"**

## 🔒 Seguridad

### Protección de firmas

- 🛡️ Las firmas se sirven mediante un **endpoint protegido** (`image.php`)
- 🔐 Validación con **token HMAC** generado al guardar la firma
- ✅ Solo usuarios autorizados o procesos internos (TCPDF) pueden acceder
- 🔑 URLs públicas pero **firmadas digitalmente** - no se pueden manipular

### Flujo de seguridad

\`\`\`
Firma guardada → Token HMAC generado → URL firmada → Validación en acceso
\`\`\`

## 📂 Estructura del Plugin

\`\`\`
local/sendafirmas/
├── 📄 index.php          # Interfaz principal (listado alumnos, panel firma)
├── 💾 save.php           # Guarda firma en File API y actualiza perfil
├── 🖼️ image.php          # Entrega firma validando token HMAC
├── 📁 db/                # Instalación de capacidades y ajustes
├── 🌐 lang/              # Cadenas de idioma
└── 📋 README.md          # Este archivo
\`\`\`

### Archivos principales

| Archivo | Función |
|---------|---------|
| `index.php` | Interfaz de usuario para selección y firma |
| `save.php` | Procesamiento y guardado de firmas |
| `image.php` | Servidor seguro de imágenes |
| `db/` | Definiciones de base de datos |
| `lang/` | Traducciones |

## 📜 Licencia

Este plugin se distribuye bajo la **licencia GNU GPL v3**.

Puedes modificarlo y adaptarlo libremente, respetando los términos de la licencia de Moodle.

## ✨ Autor

**Desarrollado por Senda Gestión S.L.**

🏢 Consultoría de formación y RRHH  
🌐 **Website**: [www.sendagestion.com](https://www.sendagestion.com)

---

## 🤝 Contribuir

¿Encontraste un bug o tienes una mejora? ¡Las contribuciones son bienvenidas!

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📞 Soporte

Si necesitas ayuda o tienes preguntas:

- 📧 Contacta con [Senda Gestión](https://www.sendagestion.com)
- 🐛 Reporta bugs en [Issues](https://github.com/tuusuario/local_sendafirmas/issues)

---

⭐ **¡Si te gusta este proyecto, dale una estrella!** ⭐
