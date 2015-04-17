<?php

include "vbulletin.api.php";
$vb = new vbulletin;

$l = $vb->login("http://localhost/vbulletin", "bebop", "test123");
print_r( $l );

if( $l["code"] ) {
    $t = $vb->postreply("21864", "test", "a lot of text");
    print_r( $t );
}

?>
