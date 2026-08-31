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
 * Strings for component 'enrol_eduapi', language 'ca'.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['configurationcorrect'] = 'La connexió s\'ha provat correctament.';
$string['connectionfailed'] = 'No s\'ha pogut connectar amb el proveïdor Edu-API: {$a}';
$string['connectionpartial'] = 'L\'autenticació s\'ha fet correctament, però ha fallat l\'obtenció d\'organitzacions/sessions acadèmiques: {$a}';
$string['fullsync'] = 'Sincronització completa d\'Edu-API';
$string['missingrequiredconfig'] = 'Falten un o més valors de configuració obligatoris. Comproveu que l\'ajust \'{$a}\' està configurat correctament.';
$string['offeringnotfound'] = 'No s\'ha trobat cap offering amb sourcedId \'{$a}\'.';
$string['offeringorganizationmismatch'] = 'L\'offering \'{$a}\' no pertany a l\'organització indicada.';
$string['pluginname'] = 'Edu-API';
$string['pluginname_desc'] = 'Moodle admet l\'especificació 1EdTech Edu-API v1p0 per sincronitzar organitzacions, cursos, usuaris i matriculacions.';
$string['privacy:mappingpath'] = 'Edu-API';
$string['privacy:metadata:enrol_eduapi_user_map'] = 'Un enllaç entre un identificador de persona d\'Edu-API i un usuari Moodle.';
$string['privacy:metadata:enrol_eduapi_user_map:mappedid'] = 'L\'id de l\'usuari Moodle enllaçat.';
$string['privacy:metadata:enrol_eduapi_user_map:parentid'] = 'El sourcedId d\'Edu-API de la Person.';
$string['settings_connection_clientid'] = 'ID de client';
$string['settings_connection_clientid_desc'] = 'L\'ID de client OAuth2 proporcionat pel proveïdor.';
$string['settings_connection_pagesize'] = 'Mida de pàgina';
$string['settings_connection_pagesize_desc'] = 'El nombre de registres sol·licitats per pàgina (paràmetre \'limit\') en obtenir una col·lecció.';
$string['settings_connection_root_url'] = 'URL arrel d\'Edu-API';
$string['settings_connection_root_url_desc'] = 'La URL arrel de l\'API Edu-API v1p0 del proveïdor, incloent-hi qualsevol ruta específica del proveïdor. Feu servir la URL \'servers\' declarada al document de descobriment OpenAPI del proveïdor (per exemple https://example.org/ims/eduapi/base/v1p0), no el nom d\'amfitrió del document de descobriment. S\'utilitza tal qual: no s\'hi afegeix cap ruta automàticament.';
$string['settings_connection_secret'] = 'Secret de client';
$string['settings_connection_secret_desc'] = 'El secret de client OAuth2 proporcionat pel proveïdor.';
$string['settings_connection_settings'] = 'Ajustos de connexió';
$string['settings_connection_token_url'] = 'URL del token OAuth2';
$string['settings_connection_token_url_desc'] = 'La URL de l\'endpoint de token OAuth2 Client Credentials Grant del proveïdor.';
$string['settings_create_unmatched_users'] = 'Quan no es troba cap usuari aparellat';
$string['settings_create_unmatched_users_create'] = 'Crear un usuari Moodle quan no hi ha aparellament';
$string['settings_create_unmatched_users_desc'] = 'Què cal fer amb una Person que no coincideix amb cap usuari Moodle existent.';
$string['settings_create_unmatched_users_skip'] = 'Ometre les persones sense aparellar (no sincronitzar-les)';
$string['settings_datasync'] = 'Dades a sincronitzar';
$string['settings_datasync_academic_session'] = 'Sessió acadèmica';
$string['settings_datasync_academic_session_desc'] = 'L\'única sessió acadèmica que se sincronitza. Es completa amb "Provar connexió" (a dalt). En seleccionar una sessió també s\'inclouen les sessions filles (per exemple, en seleccionar un curs escolar també s\'inclouen els seus semestres).';
$string['settings_datasync_organizations'] = 'Organitzacions';
$string['settings_datasync_organizations_desc'] = 'Les organitzacions que se sincronitzen. Es completa amb "Provar connexió" (a dalt).';
$string['settings_enrollmentstatus'] = 'Mapatge d\'estat de matriculació';
$string['settings_enrollmentstatus_action_enrol_active'] = 'Matricular actiu';
$string['settings_enrollmentstatus_action_enrol_suspended'] = 'Matricular suspès';
$string['settings_enrollmentstatus_action_ignore'] = 'Ignorar (no crear; no tocar l\'existent)';
$string['settings_enrollmentstatus_action_unenrol'] = 'Desmatricular';
$string['settings_enrollmentstatus_generic_desc'] = 'L\'acció de sincronització aplicada per a cada valor d\'EnrollmentStatusEnum d\'Edu-API. "recordStatus = deleted" sempre desmatricula, amb independència d\'aquest mapatge.';
$string['settings_enrollmentstatus_mapping_accepted'] = 'Estat de matriculació: accepted';
$string['settings_enrollmentstatus_mapping_cancelled'] = 'Estat de matriculació: cancelled';
$string['settings_enrollmentstatus_mapping_declined'] = 'Estat de matriculació: declined';
$string['settings_enrollmentstatus_mapping_deferred'] = 'Estat de matriculació: deferred';
$string['settings_enrollmentstatus_mapping_dropped'] = 'Estat de matriculació: dropped';
$string['settings_enrollmentstatus_mapping_enrolled'] = 'Estat de matriculació: enrolled';
$string['settings_enrollmentstatus_mapping_finished'] = 'Estat de matriculació: finished';
$string['settings_enrollmentstatus_mapping_interruption'] = 'Estat de matriculació: interruption';
$string['settings_enrollmentstatus_mapping_onhold'] = 'Estat de matriculació: onHold';
$string['settings_enrollmentstatus_mapping_onleave'] = 'Estat de matriculació: onLeave';
$string['settings_enrollmentstatus_mapping_pending'] = 'Estat de matriculació: pending';
$string['settings_enrollmentstatus_mapping_registered'] = 'Estat de matriculació: registered';
$string['settings_enrollmentstatus_mapping_revoked'] = 'Estat de matriculació: revoked';
$string['settings_enrollmentstatus_mapping_suspended'] = 'Estat de matriculació: suspended';
$string['settings_enrollmentstatus_mapping_withdrawn'] = 'Estat de matriculació: withdrawn';
$string['settings_enrollmentstatus_mapping_withdrawnfailing'] = 'Estat de matriculació: withdrawnFailing';
$string['settings_enrollmentstatus_mapping_withdrawnpassing'] = 'Estat de matriculació: withdrawnPassing';
$string['settings_exclude_inactive'] = 'Excloure registres inactius';
$string['settings_exclude_inactive_desc'] = 'Exclou de la sincronització les organitzacions, offerings i persones amb recordStatus \'inactive\'.';
$string['settings_keep_existing_courses'] = 'Conservar cursos existents';
$string['settings_keep_existing_courses_desc'] = 'No arxiva ni elimina els cursos Moodle que ja s\'havien sincronitzat però que han deixat d\'aparèixer a l\'origen.';
$string['settings_multilang'] = 'Suport multiidioma';
$string['settings_multilang_desc'] = 'Si s\'activa, els noms complets dels cursos, els noms de grup i el resum del curs (vegeu la configuració "Sincronitzar la descripció com a resum") es construeixen a partir de tots els idiomes disponibles, embolcallant cada recordLanguage de l\'Edu-API en una etiqueta `<span lang="..." class="multilang">...</span>`. Per mostrar aquest marcatge com a idiomes separats cal tenir activat el filtre "Contingut multiidioma" (filter/multilang) del lloc; si no ho està, es mostren els spans en brut. Si es desactiva (per defecte), es tria un únic valor: el que coincideix amb l\'idioma per defecte del lloc, si no n\'hi ha l\'anglès, i si tampoc el primer disponible.';
$string['settings_offering'] = 'Mapatge de cursos';
$string['settings_offering_level'] = 'Nivell d\'offering';
$string['settings_offering_level_changed_warning'] = 'L\'ajust "Nivell d\'offering" ha canviat des de l\'última sincronització, i ja existeixen cursos sincronitzats amb el nivell anterior. Aquests cursos es deixen intactes: aquest connector no migra cursos entre nivells d\'offering automàticament.';
$string['settings_offering_level_componentoffering'] = 'ComponentOffering (cadascun és un curs propi)';
$string['settings_offering_level_courseoffering'] = 'CourseOffering (els ComponentOffering esdevenen grups)';
$string['settings_offering_level_desc'] = 'Quina entitat d\'Edu-API esdevé curs Moodle. El nivell no triat esdevé grups dins del curs quan "Sincronitzar grups" està actiu.';
$string['settings_rolemapping'] = 'Mapatge de rols';
$string['settings_rolemapping_administrator'] = 'Mapatge de rol: Administrator';
$string['settings_rolemapping_advisor'] = 'Mapatge de rol: Advisor';
$string['settings_rolemapping_aide'] = 'Mapatge de rol: Aide';
$string['settings_rolemapping_chair'] = 'Mapatge de rol: Chair';
$string['settings_rolemapping_generic_desc'] = 'El rol Moodle assignat per a cada valor de RoleTypeEnum d\'Edu-API. Trieu "No matricular" per no matricular en absolut els usuaris amb aquest rol.';
$string['settings_rolemapping_guardian'] = 'Mapatge de rol: Guardian';
$string['settings_rolemapping_member'] = 'Mapatge de rol: Member';
$string['settings_rolemapping_notmapped'] = 'No matricular';
$string['settings_rolemapping_parent'] = 'Mapatge de rol: Parent';
$string['settings_rolemapping_proctor'] = 'Mapatge de rol: Proctor';
$string['settings_rolemapping_relative'] = 'Mapatge de rol: Relative';
$string['settings_rolemapping_staff'] = 'Mapatge de rol: Staff';
$string['settings_rolemapping_student'] = 'Mapatge de rol: Student';
$string['settings_rolemapping_teacher'] = 'Mapatge de rol: Teacher';
$string['settings_rolemapping_teachingassistant'] = 'Mapatge de rol: Teaching assistant';
$string['settings_shortname_attribute'] = 'Origen del shortname del curs';
$string['settings_shortname_attribute_desc'] = 'L\'atribut de l\'offering utilitzat com a shortname del curs Moodle.';
$string['settings_shortname_attribute_primarycode'] = 'primaryCode';
$string['settings_shortname_attribute_sourcedid'] = 'sourcedId';
$string['settings_sync_description'] = 'Sincronitzar la descripció com a resum';
$string['settings_sync_description_desc'] = 'Copia la descripció de l\'offering al resum del curs de Moodle a cada sincronització, preservant els salts de línia del proveïdor. Si es desactiva, el resum del curs mai no és escrit per la sincronització. Un offering sense descripció mai no esborra un resum de curs existent.';
$string['settings_sync_groups'] = 'Sincronitzar grups';
$string['settings_sync_groups_desc'] = 'Crea un grup de Moodle per cada offering del nivell no triat com a curs (per exemple, els ComponentOffering, quan CourseOffering és el nivell de curs). La pertinença al grup es manté sincronitzada amb les inscripcions pròpies del ComponentOffering: els usuaris inscrits a nivell de component s\'afegeixen al grup, i els usuaris donats de baixa, cancel·lats o eliminats se\'n treuen. Només aplica a un usuari que també tingui una inscripció a nivell de CourseOffering; una inscripció només de component s\'ignora.';
$string['settings_testconnection'] = 'Provar connexió';
$string['settings_testconnection_detail'] = 'Feu servir això per comprovar que els ajustos de connexió anteriors són correctes, i per actualitzar el llistat d\'organitzacions i sessions acadèmiques disponibles més avall.';
$string['settings_testconnection_link'] = 'Provar la connexió amb Edu-API';
$string['settings_user_field_department_source'] = 'Origen del departament';
$string['settings_user_field_institution_source'] = 'Origen de la institució';
$string['settings_user_field_source_desc'] = 'Opcional. L\'atribut de la Person que es copia al camp "{$a}" de l\'usuari de Moodle, amb la mateixa gramàtica que l\'ajust d\'atribut origen d\'Edu-API de més amunt: també s\'accepten `primaryEmail` i `sourcedId`, `extensions.<key>` llegeix el valor de primer nivell `extensions.<key>`, i `otherIdentifiers.<identifierType>` llegeix l\'identifier de la primera entrada d\'`otherIdentifiers` d\'aquest tipus. Deixeu-lo buit per desactivar-ho. Un atribut absent o buit deixa el camp intacte: la sincronització no l\'esborra mai. Que el camp també es mantingui actualitzat en els usuaris existents a cada sincronització depèn de l\'ajust "Actualitza els camps dels usuaris existents" de més avall.';
$string['settings_user_field_update_existing'] = 'Actualitza els camps dels usuaris existents';
$string['settings_user_field_update_existing_desc'] = 'Quan està activat (per defecte), els orígens de departament/institució configurats més amunt també s\'apliquen als usuaris ja existents a cada sincronització: qualsevol camp el valor resolt del qual difereixi de l\'emmagatzemat s\'actualitza (i només aquest camp), i es dispara l\'esdeveniment estàndard user_updated com en qualsevol altra actualització de perfil de Moodle. Quan està desactivat, aquests orígens només inicialitzen el camp en crear un usuari nou; els usuaris existents no es modifiquen mai.';
$string['settings_user_match_moodlefield'] = 'Camp Moodle';
$string['settings_user_match_moodlefield_desc'] = 'El camp d\'usuari de Moodle amb què s\'aparella una Person.';
$string['settings_user_match_source'] = 'Atribut origen d\'Edu-API';
$string['settings_user_match_source_desc'] = 'L\'atribut de la Person que es compara amb el camp Moodle anterior: `primaryEmail`, `sourcedId`, o un tipus d\'`otherIdentifiers`.';
$string['settings_user_match_source_otheridentifier'] = 'otherIdentifiers: {$a}';
$string['settings_user_match_source_primaryemail'] = 'primaryEmail';
$string['settings_user_match_source_sourcedid'] = 'sourcedId';
$string['settings_usermatching'] = 'Aparellament d\'usuaris';
$string['test_eduapi_connection'] = 'Prova la connexió amb Edu-API';
