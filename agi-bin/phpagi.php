<?php
class AGI {
    private $in, $out, $env = [];
    
    public function __construct() {
        $this->in = STDIN;
        $this->out = STDOUT;
        $this->read_environment();
    }
    
    private function read_environment() {
        while (true) {
            $line = trim(fgets($this->in));
            if ($line === '') break;
            list($k, $v) = explode(':', $line, 2);
            $this->env[trim($k)] = trim($v);
        }
    }
    
    public function verbose($msg, $level = 1) {
        fwrite($this->out, "VERBOSE \"$msg\" $level\n");
        $this->read_result();
    }
    
    public function set_variable($key, $value) {
        fwrite($this->out, "SET VARIABLE $key \"$value\"\n");
        $this->read_result();
    }
    
    public function get_variable($key) {
        fwrite($this->out, "GET VARIABLE $key\n");
        $line = trim(fgets($this->in));
        if (preg_match('/^200.*\((.*)\\)/', $line, $m)) {
            return $m[1];
        }
        return '';
    }
    
    private function read_result() {
        $line = trim(fgets($this->in));
        return $line;
    }
}
?>
