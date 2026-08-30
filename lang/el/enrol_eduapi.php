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
 * Strings for component 'enrol_eduapi', language 'el'.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['configurationcorrect'] = 'Η σύνδεση ελέγχθηκε με επιτυχία.';
$string['connectionfailed'] = 'Δεν ήταν δυνατή η σύνδεση με τον πάροχο Edu-API: {$a}';
$string['connectionpartial'] = 'Η ταυτοποίηση πέτυχε, αλλά η ανάκτηση οργανισμών/ακαδημαϊκών περιόδων απέτυχε: {$a}';
$string['fullsync'] = 'Πλήρης συγχρονισμός Edu-API';
$string['missingrequiredconfig'] = 'Μία ή περισσότερες απαιτούμενες τιμές διαμόρφωσης δεν βρέθηκαν. Βεβαιωθείτε ότι έχετε ρυθμίσει σωστά τη ρύθμιση \'{$a}\'.';
$string['offeringnotfound'] = 'Δεν βρέθηκε προσφορά με sourcedId \'{$a}\'.';
$string['offeringorganizationmismatch'] = 'Η προσφορά \'{$a}\' δεν ανήκει στον συγκεκριμένο οργανισμό.';
$string['pluginname'] = 'Edu-API';
$string['pluginname_desc'] = 'Το Moodle υποστηρίζει την προδιαγραφή 1EdTech Edu-API v1p0 για τον συγχρονισμό οργανισμών, μαθημάτων, χρηστών και εγγραφών.';
$string['privacy:mappingpath'] = 'Edu-API';
$string['privacy:metadata:enrol_eduapi_user_map'] = 'Σύνδεσμος μεταξύ ενός αναγνωριστικού προσώπου Edu-API και ενός χρήστη Moodle.';
$string['privacy:metadata:enrol_eduapi_user_map:mappedid'] = 'Το αναγνωριστικό του συνδεδεμένου χρήστη Moodle.';
$string['privacy:metadata:enrol_eduapi_user_map:parentid'] = 'Το sourcedId Edu-API του προσώπου (Person).';
$string['settings_connection_clientid'] = 'Αναγνωριστικό πελάτη (Client ID)';
$string['settings_connection_clientid_desc'] = 'Το αναγνωριστικό πελάτη OAuth2 που εκδόθηκε από τον πάροχο.';
$string['settings_connection_pagesize'] = 'Μέγεθος σελίδας';
$string['settings_connection_pagesize_desc'] = 'Ο αριθμός εγγραφών που ζητούνται ανά σελίδα (παράμετρος ερωτήματος \'limit\') κατά την ανάκτηση μιας συλλογής.';
$string['settings_connection_root_url'] = 'Ριζική διεύθυνση URL του Edu-API';
$string['settings_connection_root_url_desc'] = 'Η ριζική διεύθυνση URL του Edu-API v1p0 του παρόχου, συμπεριλαμβανομένης οποιασδήποτε διαδρομής ειδικής για τον πάροχο. Χρησιμοποιήστε τη διεύθυνση URL \'servers\' που δηλώνεται στο έγγραφο ανακάλυψης OpenAPI του παρόχου (για παράδειγμα https://example.org/ims/eduapi/base/v1p0), όχι το όνομα κεντρικού υπολογιστή του ίδιου του εγγράφου ανακάλυψης. Χρησιμοποιείται ακριβώς όπως εισάγεται: δεν προστίθεται αυτόματα καμία διαδρομή.';
$string['settings_connection_secret'] = 'Μυστικό πελάτη (Client secret)';
$string['settings_connection_secret_desc'] = 'Το μυστικό πελάτη OAuth2 που εκδόθηκε από τον πάροχο.';
$string['settings_connection_settings'] = 'Ρυθμίσεις σύνδεσης';
$string['settings_connection_token_url'] = 'Διεύθυνση URL διακριτικού OAuth2';
$string['settings_connection_token_url_desc'] = 'Η διεύθυνση URL του τελικού σημείου διακριτικού OAuth2 Client Credentials Grant του παρόχου.';
$string['settings_create_unmatched_users'] = 'Όταν δεν βρεθεί αντίστοιχος χρήστης';
$string['settings_create_unmatched_users_create'] = 'Δημιουργία χρήστη Moodle όταν δεν βρεθεί αντιστοιχία';
$string['settings_create_unmatched_users_desc'] = 'Τι θα γίνει με ένα πρόσωπο (Person) που δεν αντιστοιχεί σε κανέναν υπάρχοντα χρήστη Moodle.';
$string['settings_create_unmatched_users_skip'] = 'Παράλειψη προσώπων χωρίς αντιστοιχία (χωρίς συγχρονισμό)';
$string['settings_datasync'] = 'Δεδομένα προς συγχρονισμό';
$string['settings_datasync_academic_session'] = 'Ακαδημαϊκή περίοδος';
$string['settings_datasync_academic_session_desc'] = 'Η μοναδική ακαδημαϊκή περίοδος προς συγχρονισμό. Συμπληρώνεται από το «Έλεγχος σύνδεσης» παραπάνω. Η επιλογή μιας περιόδου περιλαμβάνει και τις θυγατρικές της περιόδους (για παράδειγμα, η επιλογή ενός σχολικού έτους περιλαμβάνει και τα εξάμηνά του).';
$string['settings_datasync_organizations'] = 'Οργανισμοί';
$string['settings_datasync_organizations_desc'] = 'Οι οργανισμοί προς συγχρονισμό. Συμπληρώνεται από το «Έλεγχος σύνδεσης» παραπάνω.';
$string['settings_enrollmentstatus'] = 'Αντιστοίχιση κατάστασης εγγραφής';
$string['settings_enrollmentstatus_action_enrol_active'] = 'Εγγραφή ως ενεργή';
$string['settings_enrollmentstatus_action_enrol_suspended'] = 'Εγγραφή ως ανασταλμένη';
$string['settings_enrollmentstatus_action_ignore'] = 'Παράβλεψη (χωρίς δημιουργία, οι υπάρχουσες παραμένουν ως έχουν)';
$string['settings_enrollmentstatus_action_unenrol'] = 'Απεγγραφή';
$string['settings_enrollmentstatus_generic_desc'] = 'Η ενέργεια συγχρονισμού που εφαρμόζεται για κάθε τιμή Edu-API EnrollmentStatusEnum. Το «recordStatus = deleted» απεγγράφει πάντα, ανεξάρτητα από αυτή την αντιστοίχιση.';
$string['settings_enrollmentstatus_mapping_accepted'] = 'Κατάσταση εγγραφής: Αποδεκτή (Accepted)';
$string['settings_enrollmentstatus_mapping_cancelled'] = 'Κατάσταση εγγραφής: Ακυρωμένη (Cancelled)';
$string['settings_enrollmentstatus_mapping_declined'] = 'Κατάσταση εγγραφής: Απορριφθείσα (Declined)';
$string['settings_enrollmentstatus_mapping_deferred'] = 'Κατάσταση εγγραφής: Αναβληθείσα (Deferred)';
$string['settings_enrollmentstatus_mapping_dropped'] = 'Κατάσταση εγγραφής: Εγκαταλειφθείσα (Dropped)';
$string['settings_enrollmentstatus_mapping_enrolled'] = 'Κατάσταση εγγραφής: Εγγεγραμμένος (Enrolled)';
$string['settings_enrollmentstatus_mapping_finished'] = 'Κατάσταση εγγραφής: Ολοκληρωμένη (Finished)';
$string['settings_enrollmentstatus_mapping_interruption'] = 'Κατάσταση εγγραφής: Διακοπή (Interruption)';
$string['settings_enrollmentstatus_mapping_onhold'] = 'Κατάσταση εγγραφής: Σε αναμονή (On hold)';
$string['settings_enrollmentstatus_mapping_onleave'] = 'Κατάσταση εγγραφής: Σε άδεια (On leave)';
$string['settings_enrollmentstatus_mapping_pending'] = 'Κατάσταση εγγραφής: Εκκρεμής (Pending)';
$string['settings_enrollmentstatus_mapping_registered'] = 'Κατάσταση εγγραφής: Καταχωρισμένος (Registered)';
$string['settings_enrollmentstatus_mapping_revoked'] = 'Κατάσταση εγγραφής: Ανακληθείσα (Revoked)';
$string['settings_enrollmentstatus_mapping_suspended'] = 'Κατάσταση εγγραφής: Ανασταλμένη (Suspended)';
$string['settings_enrollmentstatus_mapping_withdrawn'] = 'Κατάσταση εγγραφής: Αποχώρηση (Withdrawn)';
$string['settings_enrollmentstatus_mapping_withdrawnfailing'] = 'Κατάσταση εγγραφής: Αποχώρηση με αποτυχία (Withdrawn failing)';
$string['settings_enrollmentstatus_mapping_withdrawnpassing'] = 'Κατάσταση εγγραφής: Αποχώρηση με επιτυχία (Withdrawn passing)';
$string['settings_exclude_inactive'] = 'Εξαίρεση ανενεργών εγγραφών';
$string['settings_exclude_inactive_desc'] = 'Εξαίρεση από τον συγχρονισμό των οργανισμών, προσφορών και προσώπων με recordStatus \'inactive\'.';
$string['settings_keep_existing_courses'] = 'Διατήρηση υπαρχόντων μαθημάτων';
$string['settings_keep_existing_courses_desc'] = 'Να μην αρχειοθετούνται ούτε να αφαιρούνται μαθήματα Moodle που συγχρονίστηκαν προηγουμένως αλλά δεν εμφανίζονται πλέον στην πηγή.';
$string['settings_offering'] = 'Αντιστοίχιση μαθημάτων';
$string['settings_offering_level'] = 'Επίπεδο προσφοράς';
$string['settings_offering_level_changed_warning'] = 'Η ρύθμιση «Επίπεδο προσφοράς» άλλαξε από τον τελευταίο συγχρονισμό και υπάρχουν ήδη μαθήματα στο προηγούμενο επίπεδο. Αυτά τα μαθήματα παραμένουν ως έχουν: το πρόσθετο δεν μεταφέρει αυτόματα μαθήματα μεταξύ επιπέδων προσφοράς.';
$string['settings_offering_level_componentoffering'] = 'ComponentOffering (κάθε ένα γίνεται ξεχωριστό μάθημα)';
$string['settings_offering_level_courseoffering'] = 'CourseOffering (τα ComponentOfferings γίνονται ομάδες)';
$string['settings_offering_level_desc'] = 'Ποια προσφορά Edu-API γίνεται μάθημα Moodle. Το επίπεδο που δεν επιλέγεται γίνεται ομάδες μέσα στο μάθημα όταν είναι ενεργοποιημένος ο «Συγχρονισμός ομάδων».';
$string['settings_rolemapping'] = 'Αντιστοίχιση ρόλων';
$string['settings_rolemapping_administrator'] = 'Αντιστοίχιση ρόλου: Διαχειριστής (Administrator)';
$string['settings_rolemapping_advisor'] = 'Αντιστοίχιση ρόλου: Σύμβουλος (Advisor)';
$string['settings_rolemapping_aide'] = 'Αντιστοίχιση ρόλου: Βοηθός (Aide)';
$string['settings_rolemapping_chair'] = 'Αντιστοίχιση ρόλου: Πρόεδρος (Chair)';
$string['settings_rolemapping_generic_desc'] = 'Ο ρόλος Moodle που ανατίθεται για κάθε τιμή Edu-API RoleTypeEnum. Επιλέξτε «Να μη γίνει εγγραφή» για να παραλείπονται εντελώς οι χρήστες με αυτόν τον ρόλο.';
$string['settings_rolemapping_guardian'] = 'Αντιστοίχιση ρόλου: Κηδεμόνας (Guardian)';
$string['settings_rolemapping_member'] = 'Αντιστοίχιση ρόλου: Μέλος (Member)';
$string['settings_rolemapping_notmapped'] = 'Να μη γίνει εγγραφή';
$string['settings_rolemapping_parent'] = 'Αντιστοίχιση ρόλου: Γονέας (Parent)';
$string['settings_rolemapping_proctor'] = 'Αντιστοίχιση ρόλου: Επιτηρητής (Proctor)';
$string['settings_rolemapping_relative'] = 'Αντιστοίχιση ρόλου: Συγγενής (Relative)';
$string['settings_rolemapping_staff'] = 'Αντιστοίχιση ρόλου: Προσωπικό (Staff)';
$string['settings_rolemapping_student'] = 'Αντιστοίχιση ρόλου: Φοιτητής (Student)';
$string['settings_rolemapping_teacher'] = 'Αντιστοίχιση ρόλου: Διδάσκων (Teacher)';
$string['settings_rolemapping_teachingassistant'] = 'Αντιστοίχιση ρόλου: Βοηθός διδασκαλίας (Teaching assistant)';
$string['settings_shortname_attribute'] = 'Πηγή σύντομου ονόματος μαθήματος';
$string['settings_shortname_attribute_desc'] = 'Το χαρακτηριστικό της προσφοράς που χρησιμοποιείται ως σύντομο όνομα του μαθήματος Moodle.';
$string['settings_shortname_attribute_primarycode'] = 'primaryCode';
$string['settings_shortname_attribute_sourcedid'] = 'sourcedId';
$string['settings_sync_groups'] = 'Συγχρονισμός ομάδων';
$string['settings_sync_groups_desc'] = 'Δημιουργία ομάδας Moodle για κάθε προσφορά στο επίπεδο που δεν επιλέχθηκε ως μάθημα (π.χ. ComponentOfferings, όταν το CourseOffering είναι το επίπεδο μαθήματος). Η συμμετοχή στην ομάδα παραμένει συγχρονισμένη με τις εγγραφές του ίδιου του ComponentOffering: οι χρήστες που είναι εγγεγραμμένοι σε επίπεδο component προστίθενται στην ομάδα, ενώ όσοι αποσύρονται, ακυρώνονται ή διαγράφονται αφαιρούνται. Ισχύει μόνο για χρήστη που διαθέτει επίσης εγγραφή σε επίπεδο CourseOffering· μια εγγραφή μόνο σε επίπεδο component αγνοείται.';
$string['settings_testconnection'] = 'Έλεγχος σύνδεσης';
$string['settings_testconnection_detail'] = 'Χρησιμοποιήστε το για να ελέγξετε ότι οι παραπάνω ρυθμίσεις σύνδεσης είναι σωστές και για να ανανεώσετε τη λίστα των διαθέσιμων οργανισμών και ακαδημαϊκών περιόδων παρακάτω.';
$string['settings_testconnection_link'] = 'Έλεγχος της σύνδεσης Edu-API';
$string['settings_user_field_department_source'] = 'Πηγή τμήματος';
$string['settings_user_field_institution_source'] = 'Πηγή ιδρύματος';
$string['settings_user_field_source_desc'] = 'Προαιρετικό. Το χαρακτηριστικό του προσώπου (Person) που αντιγράφεται στο πεδίο "{$a}" του χρήστη Moodle, με την ίδια γραμματική όπως η παραπάνω ρύθμιση χαρακτηριστικού πηγής Edu-API: γίνονται επίσης δεκτά τα `primaryEmail` και `sourcedId`, το `extensions.<key>` διαβάζει την τιμή ανωτάτου επιπέδου `extensions.<key>`, και το `otherIdentifiers.<identifierType>` διαβάζει το identifier της πρώτης εγγραφής `otherIdentifiers` αυτού του τύπου. Αφήστε το κενό για απενεργοποίηση. Ένα απόν ή κενό χαρακτηριστικό αφήνει το πεδίο ανέπαφο: δεν αδειάζει ποτέ από τον συγχρονισμό. Το αν το πεδίο ενημερώνεται επίσης σε υπάρχοντες χρήστες σε κάθε συγχρονισμό καθορίζεται από τη ρύθμιση "Ενημέρωση πεδίων υπαρχόντων χρηστών" παρακάτω.';
$string['settings_user_field_update_existing'] = 'Ενημέρωση πεδίων υπαρχόντων χρηστών';
$string['settings_user_field_update_existing_desc'] = 'Όταν είναι ενεργοποιημένο (προεπιλογή), οι πηγές τμήματος/ιδρύματος που ρυθμίστηκαν παραπάνω εφαρμόζονται επίσης σε ήδη υπάρχοντες χρήστες σε κάθε συγχρονισμό: κάθε πεδίο του οποίου η επιλυμένη τιμή διαφέρει από την αποθηκευμένη ενημερώνεται (και μόνο αυτό το πεδίο), ενεργοποιώντας το τυπικό συμβάν user_updated όπως σε κάθε άλλη ενημέρωση προφίλ Moodle. Όταν είναι απενεργοποιημένο, αυτές οι πηγές αρχικοποιούν μόνο το πεδίο κατά τη δημιουργία νέου χρήστη· οι υπάρχοντες χρήστες δεν τροποποιούνται ποτέ.';
$string['settings_user_match_moodlefield'] = 'Πεδίο Moodle';
$string['settings_user_match_moodlefield_desc'] = 'Το πεδίο χρήστη Moodle με το οποίο αντιστοιχίζεται ένα πρόσωπο (Person).';
$string['settings_user_match_source'] = 'Χαρακτηριστικό πηγής Edu-API';
$string['settings_user_match_source_desc'] = 'Το χαρακτηριστικό του προσώπου (Person) που αντιστοιχίζεται με το παραπάνω πεδίο Moodle: `primaryEmail`, `sourcedId`, ή ένας τύπος `otherIdentifiers`.';
$string['settings_user_match_source_otheridentifier'] = 'otherIdentifiers: {$a}';
$string['settings_user_match_source_primaryemail'] = 'primaryEmail';
$string['settings_user_match_source_sourcedid'] = 'sourcedId';
$string['settings_usermatching'] = 'Αντιστοίχιση χρηστών';
$string['test_eduapi_connection'] = 'Έλεγχος σύνδεσης Edu-API';
