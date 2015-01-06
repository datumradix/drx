<?php
$fh = fopen('./protected/runtime/sendgridLogs/hellorajuj-sendgrid.log', 'a+');
if ( $fh )
{
    // Dump body
    fwrite($fh, print_r($HTTP_RAW_POST_DATA, true));
    fclose($fh);
}
echo "ok";
?>