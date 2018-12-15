# ical-viewer
An API to generate a JSON for one or more iCal feeds.

# How to use
In index.php, add the iCal URIs which you want to display to the $calendars array.
```php
<?php
    // Which calendars to index
    $calendars = array(
        'https://www.domain.com/link/ical.ics',
        'http://example.org/path/ical'
    );
?>
```