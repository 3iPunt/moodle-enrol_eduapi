# Edu-API (`enrol_eduapi`)

Método de matriculación que sincroniza organizaciones, cursos, usuarios y
matrículas desde un proveedor externo compatible con la especificación
1EdTech Edu-API v1p0. No modifica el núcleo de Moodle ni el tema: es un
plugin de matriculación automático, sin interfaz de matriculación manual.

## Qué hace

- Sincroniza las organizaciones del proveedor como categorías de curso de
  Moodle, respetando su jerarquía.
- Sincroniza cursos (y, según configuración, grupos) a partir de los
  `offerings` del proveedor.
- Sincroniza usuarios, emparejándolos con los ya existentes en Moodle según
  el criterio configurado.
- Sincroniza las matrículas y el rol de cada usuario en cada curso.

## Cómo funciona

- La sincronización es automática: se ejecuta mediante una tarea programada
  y no requiere intervención manual del profesorado ni del alumnado.
- La conexión con el proveedor se autentica con OAuth2 (Client Credentials
  Grant); las credenciales se configuran una única vez en los ajustes del
  plugin.
- El plugin no borra cursos ni usuarios: cuando un elemento deja de existir
  en el origen, según la configuración se desmatricula, se suspende la
  matrícula o se deja intacto.
- Si se desactiva el plugin, deja de sincronizar, pero no elimina los
  cursos, usuarios ni matrículas ya creados.

## Requisitos

- Moodle 4.5 o superior.
- No requiere otros plugins.
- Acceso de red desde el servidor de Moodle al proveedor Edu-API
  configurado.

## Instalación

1. Copiar el código en `enrol/eduapi/`.
2. Completar la instalación desde **Administración del sitio › Notificaciones**
   (o por línea de comandos: `php admin/cli/upgrade.php --non-interactive`).
3. Purgar las cachés (**Administración del sitio › Desarrollo › Purgar cachés**
   o `php admin/cli/purge_caches.php`).

## Ajustes

Este plugin todavía no tiene página de ajustes en esta versión inicial: solo
incluye el esqueleto instalable y el cliente de conexión. La página completa
de configuración (conexión, mapeo de roles, alcance de la sincronización,
etc.) se añade en una fase de desarrollo posterior.

## Desinstalación

El plugin se desinstala limpiamente desde
**Administración del sitio › Extensiones**; se elimina la tabla de
correspondencia de usuarios (`enrol_eduapi_user_map`), pero no se eliminan
los cursos, categorías, usuarios ni matrículas ya creados en Moodle.

## Licencia

GNU GPL v3 or later — 2026 3iPunt (contacte@tresipunt.com)
