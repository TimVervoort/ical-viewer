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
      
        $cacheFile = $this->CACHE_DIR.$name.$this->CACHE_EXT;
        $file = fopen($cacheFile,'w');
        fwrite($file, $content);
        fclose($file);
      
    }

    public function readCache($name) {
    
        $cacheFile = $this->CACHE_DIR.$name.$this->CACHE_EXT;
        if (!file_exists($cacheFile)) { return null; }
        $content = file_get_contents(($cacheFile));
        return $content;
    
    }

    public function needUpdate($name) {
    
        $cacheFile = $this->CACHE_DIR.$name.$this->CACHE_EXT;
        if (!file_exists($cacheFile)) { return true; }
        if (time() - filemtime($cacheFile) > $this->CACHE_LIFE) { return true; }
        return false;
    
    }
}

?>