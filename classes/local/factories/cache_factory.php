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
 * Edu-API Enrolment Client.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_eduapi\local\factories;

use cache;
use cache_store;
use enrol_eduapi\local\interfaces\cache_factory as cache_factory_interface;

/**
 * Edu-API Cache Factory.
 *
 * Uses `cache::make_from_params()` (an ad-hoc request cache that needs no static definition) rather
 * than `cache::make()` (which requires an entry in db/caches.php). That file is not introduced until
 * development phase 5, once the full sync orchestrator actually needs request-scoped caching across a
 * single run; this factory is written so it already works correctly before that file exists, and can be
 * left as-is (or upgraded to statically-defined caches, a config-only change) once it does.
 *
 * @package    enrol_eduapi
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cache_factory extends abstract_factory implements cache_factory_interface {

    /** @var cache[] Caches already created, keyed by area name */
    protected $caches = [];

    /**
     * Get a request-scoped cache for the specified area.
     *
     * @param   string $area A short, stable area name (e.g. 'v1p0_organizations')
     * @return  cache
     */
    protected function get_cache(string $area): cache {
        if (!isset($this->caches[$area])) {
            $this->caches[$area] = cache::make_from_params(cache_store::MODE_REQUEST, 'enrol_eduapi', $area);
        }

        return $this->caches[$area];
    }

    /**
     * Get the organization entity cache.
     *
     * @return  cache
     */
    public function get_organization_cache(): cache {
        return $this->get_cache('v1p0_organizations');
    }

    /**
     * Get the academic session entity cache.
     *
     * @return  cache
     */
    public function get_academic_session_cache(): cache {
        return $this->get_cache('v1p0_academic_sessions');
    }

    /**
     * Get the person entity cache.
     *
     * @return  cache
     */
    public function get_person_cache(): cache {
        return $this->get_cache('v1p0_persons');
    }

    /**
     * Get the course offering entity cache.
     *
     * @return  cache
     */
    public function get_course_offering_cache(): cache {
        return $this->get_cache('v1p0_course_offerings');
    }

    /**
     * Get the component offering entity cache.
     *
     * @return  cache
     */
    public function get_component_offering_cache(): cache {
        return $this->get_cache('v1p0_component_offerings');
    }

    /**
     * Get the enrollment entity cache.
     *
     * @return  cache
     */
    public function get_enrollment_cache(): cache {
        return $this->get_cache('v1p0_enrollments');
    }

    /**
     * Get the affiliation entity cache.
     *
     * @return  cache
     */
    public function get_affiliation_cache(): cache {
        return $this->get_cache('v1p0_affiliations');
    }

    /**
     * Purge every cache created by this factory.
     */
    public function purge_cache(): void {
        foreach ($this->caches as $cache) {
            $cache->purge();
        }
    }
}
