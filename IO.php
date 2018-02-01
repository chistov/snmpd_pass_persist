<?php

class _File{
    private $handle;
    public function __construct($fileName, $mode = "r") {
        $this->handle = @fopen($fileName, $mode);
        if (!$this->handle)
            throw new Exception("Error to open file: $fileName");

    }

    public function __get ($name)
    {
        return $this->handle;
    }

    public function __destruct (){
        fclose($this->handle);
    }

    function getHandle(){ return $this->handle; }
};

function DEBUG($str) {
    $g_debug = new _File("/tmp/debug.log", "a");
    fprintf($g_debug->getHandle(), "%s\n", $str);
}

function DEBUG_VAR($v) {
    ob_start();
    var_dump($v);
    return ob_get_clean();
}

?>
