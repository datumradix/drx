<?php
phpinfo();
$fh = fopen('./protected/runtime/sendgridLogs/hellorajuj-sendgrid.log', 'a+');
if ( $fh )
{
    // Dump body
    fwrite($fh, print_r(file_get_contents("php://input"), true));
    fclose($fh);
}
echo "ok";
?>