<?php


$bconf = 'D:/okapi/okapi.bconf';
        
$filesize = filesize($bconf);
// open file for reading in binary mode
$fp = fopen($bconf, 'rb');
// read the entire file into a binary string
$binary = fread($fp, $filesize);
 file_put_contents(
    'D:/okapi/okapi1.bconf',
    $binary  
);
//error_log($binary);
        
     