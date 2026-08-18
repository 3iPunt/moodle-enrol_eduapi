<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'enrol_eduapi', language 'es'.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['configurationcorrect'] = 'La conexión se ha probado correctamente.';
$string['connectionfailed'] = 'No se ha podido conectar con el proveedor Edu-API: {$a}';
$string['connectionpartial'] = 'La autenticación se ha realizado correctamente, pero ha fallado la obtención de organizaciones/sesiones académicas: {$a}';
$string['fullsync'] = 'Sincronización completa de Edu-API';
$string['missingrequiredconfig'] = 'Faltan uno o varios valores de configuración obligatorios. Compruebe que el ajuste \'{$a}\' está configurado correctamente.';
$string['offeringnotfound'] = 'No se ha encontrado ningún offering con sourcedId \'{$a}\'.';
$string['offeringorganizationmismatch'] = 'El offering \'{$a}\' no pertenece a la organización indicada.';
$string['pluginname'] = 'Edu-API';
$string['pluginname_desc'] = 'Moodle admite la especificación 1EdTech Edu-API v1p0 para sincronizar organizaciones, cursos, usuarios y matrículas.';
$string['privacy:mappingpath'] = 'Edu-API';
$string['privacy:metadata:enrol_eduapi_user_map'] = 'Un enlace entre un identificador de persona de Edu-API y un usuario Moodle.';
$string['privacy:metadata:enrol_eduapi_user_map:mappedid'] = 'El id del usuario Moodle enlazado.';
$string['privacy:metadata:enrol_eduapi_user_map:parentid'] = 'El sourcedId de Edu-API de la Person.';
$string['settings_connection_clientid'] = 'ID de cliente';
$string['settings_connection_clientid_desc'] = 'El ID de cliente OAuth2 proporcionado por el proveedor.';
$string['settings_connection_pagesize'] = 'Tamaño de página';
$string['settings_connection_pagesize_desc'] = 'El número de registros solicitados por página (parámetro \'limit\') al obtener una colección.';
$string['settings_connection_root_url'] = 'URL raíz de Edu-API';
$string['settings_connection_root_url_desc'] = 'La URL raíz de la API Edu-API v1p0 del proveedor, incluyendo cualquier ruta específica del proveedor. Se usa tal cual: no se añade ninguna ruta automáticamente.';
$string['settings_connection_secret'] = 'Secreto de cliente';
$string['settings_connection_secret_desc'] = 'El secreto de cliente OAuth2 proporcionado por el proveedor.';
$string['settings_connection_settings'] = 'Ajustes de conexión';
$string['settings_connection_token_url'] = 'URL del token OAuth2';
$string['settings_connection_token_url_desc'] = 'La URL del endpoint de token OAuth2 Client Credentials Grant del proveedor.';
$string['settings_create_unmatched_users'] = 'Cuando no se encuentra un usuario emparejado';
$string['settings_create_unmatched_users_create'] = 'Crear un usuario Moodle cuando no hay emparejamiento';
$string['settings_create_unmatched_users_desc'] = 'Qué hacer con una Person que no coincide con ningún usuario Moodle existente.';
$string['settings_create_unmatched_users_skip'] = 'Omitir personas sin emparejar (no sincronizarlas)';
$string['settings_datasync'] = 'Datos a sincronizar';
$string['settings_datasync_academic_session'] = 'Sesión académica';
$string['settings_datasync_academic_session_desc'] = 'La única sesión académica que se sincroniza. Se completa con "Probar conexión" (arriba).';
$string['settings_datasync_organizations'] = 'Organizaciones';
$string['settings_datasync_organizations_desc'] = 'Las organizaciones que se sincronizan. Se completa con "Probar conexión" (arriba).';
$string['settings_enrollmentstatus'] = 'Mapeo de estado de matrícula';
$string['settings_enrollmentstatus_action_enrol_active'] = 'Matricular activo';
$string['settings_enrollmentstatus_action_enrol_suspended'] = 'Matricular suspendido';
$string['settings_enrollmentstatus_action_ignore'] = 'Ignorar (no crear; no tocar la existente)';
$string['settings_enrollmentstatus_action_unenrol'] = 'Desmatricular';
$string['settings_enrollmentstatus_generic_desc'] = 'La acción de sincronización aplicada para cada valor de EnrollmentStatusEnum de Edu-API. "recordStatus = deleted" siempre desmatricula, con independencia de este mapeo.';
$string['settings_enrollmentstatus_mapping_accepted'] = 'Estado de matrícula: accepted';
$string['settings_enrollmentstatus_mapping_cancelled'] = 'Estado de matrícula: cancelled';
$string['settings_enrollmentstatus_mapping_declined'] = 'Estado de matrícula: declined';
$string['settings_enrollmentstatus_mapping_deferred'] = 'Estado de matrícula: deferred';
$string['settings_enrollmentstatus_mapping_dropped'] = 'Estado de matrícula: dropped';
$string['settings_enrollmentstatus_mapping_enrolled'] = 'Estado de matrícula: enrolled';
$string['settings_enrollmentstatus_mapping_finished'] = 'Estado de matrícula: finished';
$string['settings_enrollmentstatus_mapping_interruption'] = 'Estado de matrícula: interruption';
$string['settings_enrollmentstatus_mapping_onhold'] = 'Estado de matrícula: onHold';
$string['settings_enrollmentstatus_mapping_onleave'] = 'Estado de matrícula: onLeave';
$string['settings_enrollmentstatus_mapping_pending'] = 'Estado de matrícula: pending';
$string['settings_enrollmentstatus_mapping_registered'] = 'Estado de matrícula: registered';
$string['settings_enrollmentstatus_mapping_revoked'] = 'Estado de matrícula: revoked';
$string['settings_enrollmentstatus_mapping_suspended'] = 'Estado de matrícula: suspended';
$string['settings_enrollmentstatus_mapping_withdrawn'] = 'Estado de matrícula: withdrawn';
$string['settings_enrollmentstatus_mapping_withdrawnfailing'] = 'Estado de matrícula: withdrawnFailing';
$string['settings_enrollmentstatus_mapping_withdrawnpassing'] = 'Estado de matrícula: withdrawnPassing';
$string['settings_exclude_inactive'] = 'Excluir registros inactivos';
$string['settings_exclude_inactive_desc'] = 'Excluye de la sincronización las organizaciones, offerings y personas cuyo recordStatus sea \'inactive\'.';
$string['settings_keep_existing_courses'] = 'Conservar cursos existentes';
$string['settings_keep_existing_courses_desc'] = 'No archiva ni elimina los cursos Moodle que ya se sincronizaron pero que han dejado de aparecer en el origen.';
$string['settings_offering'] = 'Mapeo de cursos';
$string['settings_offering_level'] = 'Nivel de offering';
$string['settings_offering_level_changed_warning'] = 'El ajuste "Nivel de offering" ha cambiado desde la última sincronización, y ya existen cursos sincronizados con el nivel anterior. Esos cursos se dejan intactos: este plugin no migra cursos entre niveles de offering automáticamente.';
$string['settings_offering_level_componentoffering'] = 'ComponentOffering (cada uno es un curso propio)';
$string['settings_offering_level_courseoffering'] = 'CourseOffering (los ComponentOffering se convierten en grupos)';
$string['settings_offering_level_desc'] = 'Qué entidad de Edu-API se convierte en curso Moodle. El nivel no elegido se convierte en grupos dentro del curso cuando "Sincronizar grupos" está activo.';
$string['settings_rolemapping'] = 'Mapeo de roles';
$string['settings_rolemapping_administrator'] = 'Mapeo de rol: Administrator';
$string['settings_rolemapping_advisor'] = 'Mapeo de rol: Advisor';
$string['settings_rolemapping_aide'] = 'Mapeo de rol: Aide';
$string['settings_rolemapping_chair'] = 'Mapeo de rol: Chair';
$string['settings_rolemapping_generic_desc'] = 'El rol Moodle asignado para cada valor de RoleTypeEnum de Edu-API. Elija "No matricular" para no matricular en absoluto a los usuarios con ese rol.';
$string['settings_rolemapping_guardian'] = 'Mapeo de rol: Guardian';
$string['settings_rolemapping_member'] = 'Mapeo de rol: Member';
$string['settings_rolemapping_notmapped'] = 'No matricular';
$string['settings_rolemapping_parent'] = 'Mapeo de rol: Parent';
$string['settings_rolemapping_proctor'] = 'Mapeo de rol: Proctor';
$string['settings_rolemapping_relative'] = 'Mapeo de rol: Relative';
$string['settings_rolemapping_staff'] = 'Mapeo de rol: Staff';
$string['settings_rolemapping_student'] = 'Mapeo de rol: Student';
$string['settings_rolemapping_teacher'] = 'Mapeo de rol: Teacher';
$string['settings_rolemapping_teachingassistant'] = 'Mapeo de rol: Teaching assistant';
$string['settings_shortname_attribute'] = 'Origen del shortname del curso';
$string['settings_shortname_attribute_desc'] = 'El atributo del offering usado como shortname del curso Moodle.';
$string['settings_shortname_attribute_primarycode'] = 'primaryCode';
$string['settings_shortname_attribute_sourcedid'] = 'sourcedId';
$string['settings_sync_groups'] = 'Sincronizar grupos';
$string['settings_sync_groups_desc'] = 'Crea un grupo de Moodle por cada offering del nivel no elegido como curso (por ejemplo, los ComponentOffering, cuando CourseOffering es el nivel de curso).';
$string['settings_testconnection'] = 'Probar conexión';
$string['settings_testconnection_detail'] = 'Utilice esto para comprobar que los ajustes de conexión anteriores son correctos, y para actualizar el listado de organizaciones y sesiones académicas disponibles más abajo.';
$string['settings_testconnection_link'] = 'Probar la conexión con Edu-API';
$string['settings_user_match_moodlefield'] = 'Campo Moodle';
$string['settings_user_match_moodlefield_desc'] = 'El campo de usuario de Moodle contra el que emparejar una Person.';
$string['settings_user_match_source'] = 'Atributo origen de Edu-API';
$string['settings_user_match_source_desc'] = 'El atributo de la Person que se compara con el campo Moodle anterior.';
$string['settings_user_match_source_otheridentifier'] = 'otherIdentifiers: {$a}';
$string['settings_user_match_source_primaryemail'] = 'primaryEmail';
$string['settings_user_match_source_sourcedid'] = 'sourcedId';
$string['settings_usermatching'] = 'Emparejamiento de usuarios';
$string['test_eduapi_connection'] = 'Probar la conexión con Edu-API';
