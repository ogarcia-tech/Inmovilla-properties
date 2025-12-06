# Inmovilla Properties Plugin

Plugin profesional para conectar WordPress con Inmovilla CRM. Incluye URLs SEO-friendly, sistema de caché, shortcodes y panel de administración completo.

## Características

✅ **Conexión API** con Inmovilla CRM
✅ **URLs SEO-friendly** completamente indexables
✅ **Sistema de caché** inteligente  
✅ **Panel de administración** completo
✅ **Shortcodes flexibles**
✅ **Plantillas responsivas**
✅ **Sistema de favoritos**
✅ **Sitemaps automáticos**
✅ **Schema markup** para SEO

## Instalación

1. Subir el plugin a `/wp-content/plugins/inmovilla-properties/`
2. Activar el plugin desde el panel de WordPress
3. Ir a Ajustes → Inmovilla Properties
4. Configurar el token API de Inmovilla
5. ¡Listo para usar!

## Shortcodes Disponibles

### [inmovilla_properties]
Muestra un listado de propiedades.

**Parámetros:**
- `limit` - Número de propiedades a mostrar (default: 12)
- `type` - Filtrar por tipo de propiedad
- `location` - Filtrar por ubicación
- `layout` - Diseño: grid o list (default: grid)

**Ejemplo:**
```
[inmovilla_properties limit="9" type="piso" location="madrid"]
```

### [inmovilla_search]
Formulario de búsqueda de propiedades.

**Parámetros:**
- `layout` - horizontal o vertical (default: horizontal)
- `show_advanced` - Mostrar búsqueda avanzada (default: true)

**Ejemplo:**
```
[inmovilla_search layout="horizontal"]
```

### [inmovilla_featured]
Propiedades destacadas.

**Parámetros:**
- `limit` - Número de propiedades (default: 6)

**Ejemplo:**
```
[inmovilla_featured limit="4"]
```

## Configuración API

La API usada por este plugin es la clásica de Inmovilla basada en **Número de agencia + Contraseña** (no usa token REST).

1. En la documentación del proveedor localiza tu **numagencia** y **contraseña** de la API (ej. numagencia `2`, contraseña `82ku9xz2aw3`).
2. En WordPress ve a **Ajustes → Inmovilla Properties**.
3. Introduce el número de agencia, un sufijo si tu cuenta lo requiere (ej. `_84`) y la contraseña API.
4. Define el idioma (ID numérico de la tabla de idiomas de Inmovilla, por defecto `1` Español).
5. Guarda los cambios y pulsa **Probar conexión API** para verificar.

## Sincronización

El proceso de sincronización se ejecuta de forma periódica mediante WP-Cron. Cada ejecución procesa 20 propiedades; por ejemplo, seis ejecuciones consecutivas importarán unas 120 propiedades.

## URLs SEO

El plugin genera URLs amigables automáticamente:
- Listado: `/propiedades/`
- Propiedad individual: `/propiedad/piso-madrid-centro-ref123/`
- Búsqueda: `/buscar-propiedades/`
- Sitemap: `/sitemap-propiedades.xml`

## Metakeys para Elementor

Los siguientes metadatos están disponibles como *Dynamic Tags* en Elementor para mostrar información de las propiedades:

- `price`
- `reference`
- `bedrooms`
- `bathrooms`
- `size`
- `featured`
- `property_type`
- `location_city`
- `location_district`
- `gallery_images`
- `_inmovilla_id`

## Soporte

Para soporte técnico o consultas, contacta con el desarrollador del plugin.

## Changelog

### 1.0.0
- Versión inicial
- Conexión completa con API Inmovilla
- Sistema de URLs SEO-friendly
- Panel de administración
- Shortcodes y templates
- Sistema de caché
