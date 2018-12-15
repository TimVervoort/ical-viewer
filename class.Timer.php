<?php

class Timer {

    private $start;
    private $end;

    public function __construct() {

        $this->start = $this->getTime();
        $this->end = $this->start;

    }

    private function getTime() {

        $t = microtime();
        $t = explode(' ', $t);
        $t = $t[1] + $t[0];
        return $t;

    }

    public function getSeconds() {
        $this->end = $this->getTime();
        $t = round(($this->end - $this->start), 4); // Total duration
        return $t;
    }

}

?>