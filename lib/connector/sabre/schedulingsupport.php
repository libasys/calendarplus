<?php

namespace OCA\CalendarPlus\Connector\Sabre;

use OCA\CalendarPlus\Connector\CalendarConnector;
use OCA\CalendarPlus\Service\ObjectParser;

class SchedulingSupport extends \Sabre\CalDAV\Backend\SchedulingSupport  {
	
	private $calendarConnector;
	
	private $objectParser;
	
	
	public function __construct(){
		 $this->calendarConnector = new CalendarConnector();
		 $this->objectParser = new ObjectParser(\OCP\USER::getUser());
	}
	 /**
     * Returns a single scheduling object for the inbox collection.
     *
     * The returned array should contain the following elements:
     *   * uri - A unique basename for the object. This will be used to
     *           construct a full uri.
     *   * calendardata - The iCalendar object
     *   * lastmodified - The last modification date. Can be an int for a unix
     *                    timestamp, or a PHP DateTime object.
     *   * etag - A unique token that must change if the object changed.
     *   * size - The size of the object, in bytes.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @return array
     */
    function getSchedulingObject($principalUri, $objectUri){
    	$data = $this->calendarConnector->findObjectWhereDAVDataIs($calendarId,$objectUri);
		
    }
	
	 /**
     * Returns all scheduling objects for the inbox collection.
     *
     * These objects should be returned as an array. Every item in the array
     * should follow the same structure as returned from getSchedulingObject.
     *
     * The main difference is that 'calendardata' is optional.
     *
     * @param string $principalUri
     * @return array
     */
    function getSchedulingObjects($principalUri){
    	$raw = $this->calendarConnector->allCalendarsWherePrincipalURIIs($principalUri);
		
    }
	
	 /**
     * Deletes a scheduling object from the inbox collection.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @return void
     */
    function deleteSchedulingObject($principalUri, $objectUri);
	
	
	/**
     * Creates a new scheduling object. This should land in a users' inbox.
     *
     * @param string $principalUri
     * @param string $objectUri
     * @param string $objectData
     * @return void
     */
    function createSchedulingObject($principalUri, $objectUri, $objectData);
	
	/**
	 * @brief Creates a etag
	 * @param array $row Database result
	 * @returns associative array
	 *
	 * Adds a key "etag" to the row
	 */
	private function OCAddETag($row) {
		$row['etag'] = '"'.md5($row['calendarid'].$row['uri'].$row['calendardata'].$row['lastmodified']).'"';
		return $row;
	}
}