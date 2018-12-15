<?php

class Cache {

    private $CACHE_DIR;
    private $CACHE_EXT;
    private $CACHE_HOURS;
    private $CACHE_LIFE;

    public function __construct() {
        $this->CACHE_DIR = dirname(__FILE__).'/cache/';
        $this->CACHE_EXT = '.tim';
        $this->CACHE_HOURS = 3;
        $this->CACHE_LIFE = $this->CACHE_HOURS * 3600;
    }

    public function writeCache($name, $content) {
      
        $cacheFile = cacheLocation($name);
        $file = fopen($cacheFile,'w');
        fwrite($file, $content);
        fclose($file);
      
    }

    public function readCache($name) {
    
        $cacheFile = cacheLocation($name);
        if (!file_exists($cacheFile)) { return null; }
        $content = file_get_contents(($cacheFile));
        return $content;
    
    }

    public function needUpdate($name) {
    
        $cacheFile = cacheLocation($name);
        if (!file_exists($cacheFile)) { return true; }
        if (time() - filemtime($cacheFile) > $this->CACHE_LIFE) { return true; }
        return false;
    
    }

    private function cleanName($name) {

        $local = str_replace('https://', '', $name);
        $local = str_replace('http://', '', $local);
        $local = str_replace('www.', '', $local);
        $local = str_replace('/', '_', $local);
        $local = str_replace(' ', '_', $local);
        return $name;

    }

    private function cacheLocation($name) {

        $name = cleanName($name);
        $cacheFile = $this->CACHE_DIR.$name.$this->CACHE_EXT;
        return $cacheFile;

    }

}

?>