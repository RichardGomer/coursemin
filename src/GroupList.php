<?php

namespace COMP2213;

class GroupList {

  private $map = array();

  public function __construct($path) {
    //$path = getcwd().'/'.$path;
    $this->path = $path;

    if(!file_exists($path) || !is_file($path)) {
      throw new \Exception("Group list $path does not exist or is not a file (wd: ".getcwd().")");
    }

    $this->parse(file_get_contents($path));

    //var_dump($this->map);
  }

  public function getGroup($id) {
    $id = strtolower($id);
    if(!array_key_exists($id, $this->map)) {
      throw new \Exception("No such user as '$id' in group list");
    }

    return $this->map[$id];
  }

  public function getUsers($group) {
    $out = array();
    foreach($this->map as $un=>$g) {
      if($g == $group) {
        $out[] = $un;
      }
    }
    return $out;
  }

  public function getName($id) {
    $id = strtolower($id);
    if(!array_key_exists($id, $this->names)) {
      throw new \Exception("No such user as '$id' in group list");
    }

    return $this->names[$id];
  }

  public function getGroups() {
    return array_values(array_unique($this->map));
  }

  protected function parse($data) {
    $lines = explode("\n", $data);

    $group = false;

    foreach($lines as $l) {
      $l = trim($l);
      //echo "Process $l\n";

      if(strlen($l) < 1) { // Skip blank lines
        continue;
      }

      if(preg_match('/^Group\s*(.*)/i', $l, $matches)) { // Lines beginning "Group" set a group name/number
        $group = $matches[1];
        //echo "Set group to $group\n";
      }
      elseif(preg_match('/^([^a-z]*)([a-z0-9]+)\s(.*)/i', $l, $matches)) {
        $username = strtolower($matches[2]);
        $name = $matches[3];
        //echo "Found $username in group $group\n";

        if(array_key_exists($username, $this->map)) {
          echo "WARN: $username exists in multiple groups!\n";
        }

        $this->map[$username] = $group;
        $this->names[$username] = $name;
      }
    }

  }



}
