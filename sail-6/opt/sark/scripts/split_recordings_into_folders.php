<?php
//
// Sort monout contents into day folders in target recording directory
// N.B. run as root!
// 
//$path = "/var/spool/asterisk/monout/";
$path = "/tmp/bkup/";
$target = "/opt/sark/www/origrecs/recordings/";
if (!file_exists("$target")) {
        mkdir ($target);
        echo "created $target";
        `chown www-data:www-data $target`;
}

$dir = opendir($path);

while ($file=readdir($dir)) {
  if ($file != "." && $file != ".." && !is_dir($file)) {
    $folder = date("dmy", substr($file, 0, strpos($file, "-")));
    if (!file_exists("$target$folder")) {
        mkdir("$target$folder");
        `chown www-data:www-data $target$folder`;
        echo "Created: $target$folder \n";                                                                                                 
    }
    `/bin/mv $path$file  $target$folder/$file`;
    `chown www-data:www-data $target$folder/$file`;
  }

 }

closedir($dir);

?>
