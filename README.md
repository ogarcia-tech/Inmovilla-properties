# Inmovilla Properties Plugin

Plugin profesional para conectar WordPress con Inmovilla CRM. Incluye URLs SEO-friendly, sistema de caché, shortcodes y panel de administración completo.

## Características

✅ **Conexión API** con Inmovilla CRM
✅ **Importación diaria por XML** sin consumir la API en frontend
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
4. Introducir número de agencia, contraseña y URL del XML proporcionado por Inmovilla
5. ¡Listo para usar!

📌 **¿Dónde pegar la URL del XML?**
- En el panel de WordPress, ve a **Ajustes → Inmovilla Properties**.
- En la pestaña **API**, encontrarás el campo **“URL del Feed XML”** justo debajo de “URL Base API”.
- Pega ahí la URL completa de tu feed (ej. `https://procesos.inmovilla.com/xml/xml2demo/2-web.xml`).

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

## Configuración API y XML

La API usada por este plugin es la clásica de Inmovilla basada en **Número de agencia + Contraseña** (no usa token REST) y, para la sincronización masiva, un **feed XML diario**.

1. Localiza tu **numagencia** y **contraseña** (ej. `2` / `82ku9xz2aw3`).
2. Obtén la URL del XML que expone toda tu cartera (ej. `https://procesos.inmovilla.com/xml/xml2demo/2-web.xml`).
3. En WordPress ve a **Ajustes → Inmovilla Properties**.
4. Introduce número de agencia, contraseña y pega la **URL del feed XML**.
5. Guarda los cambios y pulsa **Probar conexión API** si necesitas validar credenciales puntuales.

## Sincronización

El proceso de sincronización diaria lee el **XML completo** y crea/actualiza los posts `inmovilla_property` en WordPress (incluyendo imagen destacada y meta datos). Se ejecuta mediante WP-Cron (intervalo diario por defecto) y elimina las propiedades que ya no aparecen en el feed.

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

## Información de desarrollo

La versión **2.0** del plugin ha sido desarrollada por **Metricaweb**. Puedes obtener más información en [www.metricaweb.es](https://www.metricaweb.es).

## Changelog

### 1.0.0
- Versión inicial
- Conexión completa con API Inmovilla
- Sistema de URLs SEO-friendly
- Panel de administración
- Shortcodes y templates
- Sistema de caché
