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

    // Libaries
    require_once('class.Cache.php');
    require_once('class.Timer.php');

    // Init helper objects
    $timer = new Timer();
    $cache = new Cache();

    // Which calendars to index
    $calendars = array(
        'https://calendar.google.com/calendar/ical/timvervoort97%40gmail.com/private-63375787132677600264c7be24745d9a/basic.ics',
        'https://uhcal.headr.be/calendars/ab2813e0-f867-11e8-a27a-6dbbb39c189e.ics'
    );

    $events = array(); // Stores all events
    $bookings = array(); // Events to be displayed

    foreach ($calendars as $calendar) {

        // Create local cache name
        $local = str_replace('https://', '', $calendar);
        $local = str_replace('http://', '', $local);
        $local = str_replace('www.', '', $local);
        $local = str_replace('/', '_', $local);
        $local = str_replace(' ', '_', $local);

        // Retreive calendar and update cache
        if ($cache->needUpdate($local) || isset($_REQUEST['forceUpdate'])) {
            $ics = file_get_contents($calendar);
            $cache->writeCache($local, $ics);
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

                            // Only events in the future
                            if (date('Y-m-d H:i:s') > $e->end) {
                                continue 2;
                            }

                        }

                        else if ($key == 'SUMMARY') {
                            $e->title = $value;
                        }

                        else if ($key == 'LOCATION') {
                            $e->location = $value;
                        }

                    }
                }

                if (empty($e->start) || empty($e->end)) {
                    continue;
                }

                array_push($events, $e);
            }
        }

    }

    // Helper function to sort events by start date
    function cmp($a, $b) {
        if ($a->start == $b->start) {
            return 0;
        }
        return ($a->start < $b->start) ? -1 : 1;
    }

    uasort($events, 'cmp'); // Order events chronological

    // Get all dates in this month
    function getDates($year, $month) {

        global $bookings;
        global $events;
        global $monthNames;

        for ($d = 1; $d <= 31; $d++) {

            $availability = new stdClass();
            $availability->date = date('Y-m-d', strtotime($year.'-'.$month.'-'.$d));
            $t = strtotime($d.'-'.$month.'-'.$year);

            // Between last monday & today, fill all
            if ($t >= strtotime('monday this week') && $t < strtotime('now')) {

                $p = new stdClass();
                $p->start = date('Y-m-d H:i:s', $t);
                $p->end = date('Y-m-d H:i:s', strtotime($d.'-'.$month.'-'.$year.' +23 hours 59 minutes 59 seconds'));
                $p->title = '';
                $availability->slots = array($p);
                array_push($bookings, $availability);

            }

            // Today, create event until now
            else if (date('m', $t) == $month && date('Y-m-d', $t) == date('Y-m-d')) {
            
                $p = new stdClass();
                $p->start = date('Y-m-d H:i:s', $t);
                $p->end = date('Y-m-d').' '.(date('H') + 1).':00:00'; // Find most nearby hour

                $availability->slots = array($p);
                $availability->slots = array_merge($availability->slots, findReservations($events, $availability->date));
                array_push($bookings, $availability);

            }

            // Dates in the future
            else if (date('m', $t) == $month && date('Y-m-d', $t) > date('Y-m-d')) { 

                $availability->slots = findReservations($events, $availability->date);
                array_push($bookings, $availability);

            }
        }
    }

    // Find reservations in events
    function findReservations($events, $date) {
        $found = array();
        foreach ($events as $e) {
            if (strpos($e->start, $date) !== false) {
                array_push($found, $e);
            }
        }
        return $found;
    }

    // Find reservations in the next $months months
    function getNextMonths($year, $month, $months) {
        $m = $month;
        for ($i = 0; $i <= $months; $i++) {
            if ($m > 12) {
                $m = 1; // Revert to January
                $year += 1; // Jump to next year
            }
            getDates($year, $m);
            $m += 1;
        }
    }

    getNextMonths(date('Y'), date('m'), 3);

    $output = new stdClass();
    $output->meta = new stdClass();
    $output->meta->status = 'OK';
    $output->meta->duration = $timer->getSeconds();
    $output->data = $bookings;

    echo json_encode($output, JSON_PRETTY_PRINT);

?>