<?php
$key = array();
for ($x = 0; $x <= 128; $x++) {
  $key[] = floor(rand(0, 15));
} 
echo json_encode($key);
