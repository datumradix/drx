<?php
//set POST variables
//$url = 'http://localhost/zurmo-latest/app/index.php/sendGrid/external/writeLog?username=hellorajuj';
$url = 'http://rosspeetoom.zurmocloud.com/msnextdefault/app/index.php/sendGrid/external/writeLog?username=hellorajuj';
$data = '[{"email":"john.doe@sendgrid.com","timestamp":1337197600,"smtp-id":"<4FB4041F.6080505@sendgrid.com>","event":"processed"}]';

//open connection
$ch = curl_init();
echo "After curl init";
//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch,CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                            'Content-Type: application/json',
                                            'Connection: Keep-Alive'
                                            ));
curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
//execute post
$result = curl_exec($ch);
echo "After execution";
if (FALSE === $result)
{
   throw new Exception(curl_error($ch), curl_errno($ch));
}
//close connection
curl_close($ch);
echo "I am done";
?>

