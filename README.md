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

1. En Inmovilla: Ajustes → Opciones → "Token para API Rest"
2. Copiar el token generado
3. En WordPress: Ajustes → Inmovilla Properties
4. Pegar el token y guardar

## Sincronización

El proceso de sincronización se ejecuta de forma periódica mediante WP-Cron. Cada ejecución procesa 20 propiedades; por ejemplo, seis ejecuciones consecutivas importarán unas 120 propiedades.

## URLs SEO

El plugin genera URLs amigables automáticamente:
- Listado: `/propiedades/`
- Propiedad individual: `/propiedad/piso-madrid-centro-ref123/`
- Búsqueda: `/buscar-propiedades/`
- Sitemap: `/sitemap-propiedades.xml`

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
