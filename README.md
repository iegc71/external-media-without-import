External Media without Import
============================================================================
By default, adding an image to the WordPress media library requires you to import or upload the image to the WordPress site, which means there must be a copy of the image file stored in the site. This plugin enables you to add an image stored in an external site to the media library by just adding a URL linking to the remote image address. In this way you can host the images in a dedicated server other than the WordPress site, and still be able to show them by various gallery plugins which only take images from the media library.

The plugin provides buttons and inputs in the 'Media' -> 'Add New' page, the media upload panel and a dedicated Add External Media without Import submenu page. Therefore you can either add an external media before (or after) editing any post or page, or in the process of editing a post or page without interrupting the editing process.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/external-media-without-import` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

Then you can use the plugin to add external media without import.

## Usage

1. Click the 'Add New' button in the 'Media' -> 'Library' page, the media upload panel will show up, in which there's an 'Add External Media without Import' button. Click it.
2. Or click the 'Add External Media without Import' submenu in the side bar.
3. Fill in the URLs of the images you want to add. You can fill in multiple URLs, with each URL filled in one line.
4. Click the 'Add' button, the remote images will be added.

Click 'Add External Media without Import' button in the media upload panel, the 'Add a media from URL' panel will appear:

![](screenshots/screenshot-1.png)

Enter the URLs of the external media you'd like to add to the media library and click 'Add':

![](screenshots/screenshot-2.jpg)

You can fill in multiple URLs, with each URL filled in one line:

![](screenshots/screenshot-3.jpg)

You can also add an external media during the process of editing a post or page by clicking 'Add Media' -> 'Upload Files', and in the upload panel click 'Add External Media without Import'. The same input interface will appear.

Note that WordPress needs to know in advance the width and height of an image in order to correctly display it in the media library page and any post/page.  In most cases, the plugin resolves these properties automatically without worrying you. But in rare cases, the plugin may fail to get the widths and heights of the images you specify. In that case, some input fields will show up and let you fill in the properties manually.

## Changelog

He realizado varias mejoras de seguridad importantes en el plugin. Aquí están los principales cambios y mejoras:

Estructuración y Organización:

Convertido a una clase orientada a objetos para mejor organización
Namespace específico para evitar conflictos
Constantes definidas para configuración


Mejoras de Seguridad:

Validación estricta de URLs
Lista de dominios bloqueados configurable
Límite de tamaño de imagen
Tipos MIME permitidos específicos
Verificación de nonce en todas las operaciones
Headers de seguridad añadidos
Sanitización completa de entradas
Verificación de permisos de usuario


Validación de Imágenes:

Verificación de dimensiones
Comprobación de tipo MIME
Timeout en las conexiones
User-Agent personalizado


Manejo de Errores:

Mejor gestión de errores y excepciones
Respuestas JSON estructuradas
Logs de errores mejorados


Rendimiento:

Carga condicional de recursos
Optimización de consultas



Para usar este plugin de forma segura, deberías:

Asegurarte de que tu sitio usa HTTPS
Configurar la lista de dominios bloqueados según tus necesidades
Ajustar el tamaño máximo de imagen según tus requerimientos
Mantener WordPress y todos los plugins actualizados
