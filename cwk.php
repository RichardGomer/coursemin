<?php

namespace COMP2213;

use Exception;
use Garden\Cli\Cli;

require_once 'vendor/autoload.php';

$cli = Cli::create();

$cli->command("fixnames")
->description("rename files that are called something stupid like *.pdf");

$cli->command("trim")
->description("remove non-final handins");

$cli->command("grouprename")
->description("Convert folder names to group numbers")
->opt('list:l', 'path to group list (see examplegroup.txt) - defaults to groups.txt');

$cli->command("groupcheck")
->description("Check groups")
->opt('list:l', 'path to group list (see examplegroup.txt) - defaults to groups.txt');

$cli->command("grouplist")
->description("List all groups")
->opt('list:l', 'path to group list (see examplegroup.txt) - defaults to groups.txt');

$cli->command("groupmail")
->description("Convert group list to email string")
->opt('list:l', 'path to group list (see examplegroup.txt) - defaults to groups.txt')
->opt('group:g', 'group number');

$cli->command("split")
->description("split handins into -n groups")
->opt('number:n', 'number of chunks to split into', true, 'integer');

$cli->command("rename")
->description("Rename all files that match a pattern")
->opt('pattern:p', 'A filename pattern to match', true, 'string')
->opt('name:n', 'The new filename for the files', true, 'string');

$cli->command('*')->arg('path', 'The handin folder to work on', true);

$args = $cli->parse($argv);

//var_dump($args);

try {
  $dir = new CourseworkDirectory($args->getArg('path'));
} catch(\Exception $e) {
  echo $e->getMessage()."\n";
  exit;
}

chdir($args->getArg('path'));

try { // Handle exceptions gracefully

  switch($args->getCommand()) {

    case 'trim':

      $all = $dir->getHandins();
      $keep = $dir->getFinalHandins();

      // Remove non-final handins
      $rm = array_diff($all, $keep);
      foreach($rm as $r) {
        echo "Remove non-final handin $r\n";
        $dir->remove($r);
      }

    break;

    case 'grouprename':
      $list = $args->getOpt('list', 'groups.txt');

      $list = new GroupList($list);

      $all = $dir->getHandins();

      foreach($all as $k) {
        $un = $dir::getUsername($k, $ver);
        $group = $list->getGroup($un);
        echo "Move $un => group_$group";
        $dir->rename($k, 'group_'.$group.".$ver");
      }

    break;

    case 'groupcheck':
      $list = $args->getOpt('list', 'groups.txt');
      $list = new GroupList($list);

      $groups = $list->getGroups();

      $counts = [];
      $scount = 0;
      foreach($groups as $gn) {
        $members = $list->getUsers($gn);
        $count = count($members);
        $scount += $count;
        $counts[$count] = array_key_exists($count, $counts) ? $counts[$count] + 1 : 1;
      }

      foreach($counts as $count=>$freq) {
        echo "$count: $freq groups\n";
      }
      
      echo "---------\nGroups: ".count($groups)."\nStudents:".$scount."\n";

      break;


    case 'groupmail':
      $gnum = $args->getOpt('group');
      
      $list = $args->getOpt('list', 'groups.txt');
      $list = new GroupList($list);

      $group = $list->getUsers($gnum);

      foreach($group as &$un) {
        $un .= "@soton.ac.uk";
      }

      echo implode(", ", $group)."\n";

      break;

    case 'grouplist':
      $list = $args->getOpt('list', 'groups.txt');
      $list = new GroupList($list);

      $groups = $list->getGroups();

      $counts = [];
      $scount = 0;
      foreach($groups as $gn) {
        $members = $list->getUsers($gn);
        
        echo "Group ".$gn."\n";

        foreach($members as $m) {
          echo "* ".$m." ".$list->getName($m)."\n";
        }

        echo "\n\n";
      }

      break;


    case 'split':
      $n = $args->getOpt('number');
      echo "Split into $n chunks\n";

      // Create chunks
      for($i = 1; $i <= $n; $i++) {
        $cp = $dir->getPath('chunk'.$i);
        echo "Make $cp\n";
        mkdir($cp);
      }

      $keep = $dir->getFinalHandins();

      foreach($keep as $i=>$k) {
        $chunk = $i % $n + 1;

        $orig = $dir->getPath($k);
        $new = $dir->getPath('chunk'.$chunk.'/'.$k);

        echo "Move $orig => $new\n";
        rename($orig, $new);
      }

    break;

    case 'fixnames':
      $base = $dir->getPath();
      $dir->eachFile(function($path) use ($base){
        //echo $path."\n";
        $fn = basename($path);
        $dir = dirname($path);

        $nfn = preg_replace('/[^a-z\._\-0-9]/i', '_', $fn);

        if($nfn !== $fn) {
          echo "Rename $fn to $nfn\n";
          rename($base.$path, $base.$dir.'/'.$nfn);
        }

      });
    break;
    
    case 'rename':
      $base = $dir->getPath();
      $pattern = $args->getOpt('pattern');
      $name = $args->getOpt('name');
      $dir->eachFile(function($path) use ($base, $pattern, $name){
        //echo $path."\n";
        $fn = basename($path);
        $dir = dirname($path);

        if(fnmatch($pattern, $fn)) {
          echo "Rename {$base}{$path} to {$base}{$dir}/{$name}\n";
          rename($base.$path, $base.$dir.'/'.$name);
        }

      });
    break;


    default:
    echo "???";
    break;
  }

} catch(Exception $e) {
  echo "ERROR: ".$e->getMessage()."\n";
}
