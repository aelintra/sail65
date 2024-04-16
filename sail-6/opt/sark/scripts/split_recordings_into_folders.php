<?php
//
// Sort recordings into day folders in the target recording directory based upon the epoch stamp in their filename
//
// SARK sound recordings are held in the format:-
//  epoch|tenant|source number or Queue name|target exten or agent number
//
// Very occasionally, this has to be done due to a lengthy, or cross midnight, system outage of some description
// N.B. run this manually as root!
// N.B. back everything up BEFOREHAND!
//

// set the path to wherever the homeless recordings are (monstage,monout - whatever)
// N.B. BE CAREFUL with monstage in case there are queue records in there.  
//srkcronqmove needs to convert the queue records first.
//
$path = "/var/spool/asterisk/monout/";

// set the target recordings anchor folder
$target = "/opt/sark/media/recordings/";

if (!file_exists("$target")) {
        mkdir ($target);
        echo "created $target \n";
        `chown www-data:www-data $target`;
}

$dir = opendir($path);

while ($file=readdir($dir)) {
  if ($file != "." && $file != ".." && !is_dir($file)) {
    echo "processing file $file \n"; 
    $folder = date("dmy", substr($file, 0, 10));
    echo "Looking for folder $folder \n";
    if (!file_exists("$target$folder")) {
        mkdir("$target$folder");
        `chown www-data:www-data $target$folder`;
        echo "Created: $target$folder \n";
    }

    `/bin/cp $path$file  $target$folder/$file`;
    echo "Copied: $path$file to $target$folder/$file \n";
    `chown www-data:www-data $target$folder/$file`;
  }

 }

closedir($dir);

?>
