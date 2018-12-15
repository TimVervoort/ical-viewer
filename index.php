<?php

// Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allow access for this API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60)));
header('Content-type: application/json; charset=utf-8');

define('DEFAULT_LOCATION', 'Hasselt, Belgium'); // Default location when none is provided
define('MONTHS_FUTURE', 3); // Display reservations this amount of months in the future

// Libaries
require_once('class.Cache.php');
require_once('class.Timer.php');

// Init helper objects
$timer = new Timer();
$cache = new Cache();

$calendars = array(); // Which calendars to index

$events = array(); // Stores all events
$bookings = array();

foreach ($calendars as $calendar) {

    // Retreive calendar and update cache
    if ($cache->needUpdate($calendar) || isset($_REQUEST['forceUpdate'])) {
        $ics = file_get_contents($calendar);
        $cache->writeCache($calendar, $ics);
    }
    else {
        $ics = $cache->readCache($local); // Read from cache
    }

    // Get all events
    $found = explode('BEGIN:VEVENT', $ics);
    foreach ($found as $event) {

        // Copy valid events from iCal
        if (strpos($event, 'SUMMARY') !== false) {

            $e = new stdClass();
            $fields = explode("\r\n", $event);
            foreach ($fields as $field) {

                if (strpos($field, ':') !== false) {

                    $key = explode(':', $field)[0];
                    $value = explode(':', $field)[1];

                    if ($key == 'DTSTART') {
                        $e->start = date('Y-m-d H:i:s', strtotime($value));
                    }

                    else if ($key == 'DTEND') {

                        $e->end = date('Y-m-d H:i:s', strtotime($value));

                        // Revert midnight new day back to just before midnight the previous day
                        if (strpos($e->end, '00:00:00')) {
                            $e->end = date("Y-m-d H:i:s", strtotime($e->end) - 1);
                        }
                      
                        if (date('Y-m-d H:i:s') > $e->end) { continue 2; } // Only events in the future, skip all fields and go to next event

                    }
                    else if ($key == 'SUMMARY') {
                        $e->title = $value;
                    }
                    else if ($key == 'LOCATION') {
                        $e->location = $value;
                        if (empty($value)) {  $e->location = DEFAULT_LOCATION; }
                    }
                }
            }

            if (empty($e->start) || empty($e->end)) { continue; } // Not enough data, skip this element

            array_push($events, $e);

        }
    }

}

// Helper function to sort events by start date
function cmp($a, $b) {

    if ($a->start == $b->start) { return 0; }
    return ($a->start < $b->start) ? -1 : 1;

}

uasort($events, 'cmp'); // Order events chronological

function getDates($year, $month) {

    global $bookings;
    global $events;

    if (date('m') >= 12) {
        getDates(date('Y') + round($month / 12), $month % 12); // Wrap to next year
    }

    for ($d = 1; $d <= 31; $d++) {

        $time = mktime(12, 0, 0, $month, $d, $year);

        if (date('m', $time) == $month && date('Y-m-d', $time) == date('Y-m-d')) { // Current day

            $availability = new stdClass();
            $availability->date = date('Y-m-d', strtotime($year.'-'.$month.'-'.$d));

            $passed = new stdClass();
            $passed->start = date('Y-m-d').' 00:00:00';

            // Find next hour
            $hour = date('H') + 1;
            $passed->end = date('Y-m-d').' '.$hour.':00:00';
            $availability->slots = array($passed);
            $availability->slots = array_merge($availability->slots, findReservations($events, $availability->date));
            array_push($bookings, $availability);

        }

        if (date('m', $time) == $month && date('Y-m-d', $time) > date('Y-m-d')) { // Day in the future

            $availability = new stdClass();
            $availability->date = date('Y-m-d', strtotime($year.'-'.$month.'-'.$d));
            $availability->slots = findReservations($events, $availability->date);
            array_push($bookings, $availability);

        }

    }
}

function findReservations($events, $date) {

    $found = array();

    foreach ($events as $e) {
        if (strpos($e->start, $date) !== false) {
            array_push($found, $e);
        }
    }

    return $found;

}

for ($i = 0; $i <= MONTHS_FUTURE; $i++) {
    getDates(date('Y'), date('m') + $i);
}

$output = new stdClass();
$output->meta = new stdClass();
$output->meta->status = 'OK';
$output->meta->duration = $timer->getSeconds();
$output->data = $bookings;

echo json_encode($output, JSON_PRETTY_PRINT);

?>