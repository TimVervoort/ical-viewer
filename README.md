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
In index.php, add the iCal URIs which you want to display to the $calendars array.

Navigate to index.php to view the generated JSON. The output will look something like:

```json
{
  "meta": {
    "status": "OK",
    "duration": 1.2
  },
  "data": [
    {
      "date": "2018-12-30",
      "slots": []
    },
    {
      "date": "2018-12-31",
      "slots": [
        {
          "start": "2018-12-31 14:00:00",
          "end": "2018-12-31 20:59:59"
        },
        {
          "start": "2018-12-31 21:00:00",
          "end": "2018-12-31 23:59:59",
          "location": "Hasselt, Belgium",
          "title": "Some title"
        }
      ]
  }]
}
```