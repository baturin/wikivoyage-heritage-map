<?php

// Simple Counter - 2013-12-13

$fp=fopen("./lib/counter.txt","r+");
  $number=fgets($fp);
  $number++;
  rewind($fp);
  flock($fp,2);
  fputs($fp,$zahl);
  flock($fp,3);
fclose($fp);

?>