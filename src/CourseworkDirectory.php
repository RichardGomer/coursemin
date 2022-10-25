<?php

namespace COMP2213;

class CourseworkDirectory {

  function __construct($path) {
    if(!is_dir($path)) {
      throw new \Exception("'$path' does not exist");
    }

    $this->path = realpath($path).'/';
  }

  /**
   * Get a list of all handins in the directory; basically subdirectories that
   * contain
   */
  public function getHandins() {
    $out = [];

    $this->eachDir(function($rpath) use (&$out) {
        //echo "Process: $rpath\n";
        if($this->isHandin($rpath)) {
          $out[] = $rpath;
        }
      });

    return $out;
  }

  protected function isHandin($rpath) {
    $lp = $rpath.'/logfile.txt';
    //echo "Check $fullpath\n";
    $found = file_exists($fp = $this->getPath($lp));
    //echo "Look for $fp :: " . ($found ? " FOUND " : "MISSING") . "\n";
    return $found;
  }

  /**
   * List the FINAL handins in the directory
   * i.e. not ones superseded by a later one
   */
  public function getFinalHandins() {
    $children = $this->getHandins();

    $max = [];

    foreach($children as $c) {
      $v = 0;
      $un = self::getUsername($c, $v);

      if(array_key_exists($un, $max)) {
        if($v > $max[$un]) {
          $max[$un] = $v;
        }
      } else {
        $max[$un] = $v;
      }

    }

    $out = [];

    foreach($max as $un=>$v) {
      $out[] = $un.".".$v;
    }

    return $out;
  }

  public static function getUsername($dirname, &$version=null) {
    $bits = explode('/', $dirname);
    $fn = array_pop($bits);
    list($un, $version) = explode('.', $fn, 2);
    return $un;
  }


  /**
   * Get the full path of the directory, optionally with a suffix (e.g. child)
   */
  public function getPath($suffix=null) {
    return $this->path.$suffix;
  }


  /**
   * Do something to each file in the directory (recursive)
   */
  public function eachFile(Callable $fn) {
    $base = $this->getPath();

    $wfn = function($path) use ($fn, $base) {
      $fp = self::makePath($base, $path);
      if(is_file($fp) && !is_link($fp)) {
        $fn($path);
      }
    };

    $this->recurse($base, '', $wfn);
  }

  /**
   * Do something to each directory (recursive)
   */
  public function eachDir(Callable $fn) {
    $base = $this->getPath();

    $wfn = function($path) use ($fn, $base) {
      $fp = self::makePath($base, $path);
      if(is_dir($fp) && !is_link($fp)) {
        $fn($path);
      }
    };

    $this->recurse($base, '', $wfn);
  }

  /**
   * Run $fn on every file/directory in the given path; recursive
   */
  protected function recurse($base, $path, $fn) {
    //echo "Detected {$base}{$path} ";
    $objects = scandir($base.$path);
    //$n = count($objects);
    //echo " with $n children\n";
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
          $rpath = self::makePath($path, $object);
          $fn($rpath);
          $fpath = self::makePath($base, $rpath);
          if(is_dir($fpath)) {
            $this->recurse($base, $rpath, $fn);
          }
      }
    }
  }


  /**
   * Remove the named handin
   */
  public function remove($child) {

    if($this->isHandin($child)) {
      $fp = $this->getPath($child);
      assert(strlen($fp) > 24); // Avoid (some) BAD THINGS by doing a sanity check on the path to delete!
      $this->delete($fp);
    }
  }

  public function rename($child, $new) {
    $fp = $this->getPath($child);
    rename($fp, dirname($fp).'/'.$new);
  }

  protected static function makePath($a, $b) {
    if(strlen($a) > 0 && !preg_match('@/$@', $a)) {
      $a .= '/';
    }

    return $a.$b;
  }


  protected function delete($dir) {

    //echo "DELETE $fullpath\n";
    //return;

    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
            $this->delete($dir. DIRECTORY_SEPARATOR .$object);
          else
            unlink($dir. DIRECTORY_SEPARATOR .$object);
        }
      }
      rmdir($dir);
    }
  }

}
