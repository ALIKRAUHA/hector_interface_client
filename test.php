<?php

$host = '192.168.43.192';
$port = 81;
$local = "http://localhost";  //url where this script run
$data = '=rainbow';
//$data = '*0000FF';
//$data = '*00FF00';
//$data = '*FF0000';

$head = "GET / HTTP/1.1" . "\r\n" .
"Upgrade: WebSocket" . "\r\n" .
"Connection: Upgrade" . "\r\n" .
"Origin: $local" . "\r\n" .
"Host: $host" . "\r\n" .
"Sec-WebSocket-Version: 13" . "\r\n" .
"Sec-WebSocket-Key: asdasdaas76da7sd6asd6as7d" . "\r\n" .
"Content-Length: " . strlen($data) . "\r\n" . "\r\n";
var_dump($host, $port);
$sock = fsockopen($host, $port, $errno, $errstr, 2);
var_dump("errno: " . $errno);
var_dump("errstr: " . $errstr);
fwrite($sock, $head) or die('error:' . $errno . ':' . $errstr);
$headers = fread($sock, 2000);
var_dump($headers);
fwrite($sock, hybi10encode($data)) or die('error:' . $errno . ':' . $errstr);
$wsdata = fread($sock, 2000);
fclose($sock);
var_dump(hybi10Decode($wsdata));


function hybi10Decode($data)
{
    $bytes = $data;
    $dataLength = '';
    $mask = '';
    $coded_data = '';
    $decodedData = '';
    $secondByte = sprintf('%08b', ord($bytes[1]));
    $masked = ($secondByte[0] == '1') ? true : false;
    $dataLength = ($masked === true) ? ord($bytes[1]) & 127 : ord($bytes[1]);

    if ($masked === true) {
        if ($dataLength === 126) {
            $mask = substr($bytes, 4, 4);
            $coded_data = substr($bytes, 8);
        } elseif ($dataLength === 127) {
            $mask = substr($bytes, 10, 4);
            $coded_data = substr($bytes, 14);
        } else {
            $mask = substr($bytes, 2, 4);       
            $coded_data = substr($bytes, 6);       
        }   
        for ($i = 0; $i < strlen($coded_data); $i++) {       
            $decodedData .= $coded_data[$i] ^ $mask[$i % 4];
        }
    } else {
        if ($dataLength === 126) {         
            $decodedData = substr($bytes, 4);
        } elseif ($dataLength === 127) {           
            $decodedData = substr($bytes, 10);
        } else {               
            $decodedData = substr($bytes, 2);       
        }       
    }   
    return $decodedData;
}

function hybi10Encode($payload, $type = 'text', $masked = true)
{
    $frameHead = array();
    $frame = '';
    $payloadLength = strlen($payload);
    switch ($type) {
    case 'text':
        // first byte indicates FIN, Text-Frame (10000001):
        $frameHead[0] = 129;
        break;
    case 'close':
        // first byte indicates FIN, Close Frame(10001000):
        $frameHead[0] = 136;
        break;    
    case 'ping':
        // first byte indicates FIN, Ping frame (10001001):
        $frameHead[0] = 137;
        break;
    case 'pong':
        // first byte indicates FIN, Pong frame (10001010):
        $frameHead[0] = 138;
        break;
    }
    // set mask and payload length (using 1, 3 or 9 bytes)
    if ($payloadLength > 65535) {
        $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
        $frameHead[1] = ($masked === true) ? 255 : 127;
        for ($i = 0; $i < 8; $i++) {
            $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
        }
        // most significant bit MUST be 0 (close connection if frame too big)
        if ($frameHead[2] > 127) {
            $this->close(1004);
            return false;
        }
    } elseif ($payloadLength > 125) {
        $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
        $frameHead[1] = ($masked === true) ? 254 : 126;
        $frameHead[2] = bindec($payloadLengthBin[0]);
        $frameHead[3] = bindec($payloadLengthBin[1]);
    } else {
        $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
    }
    // convert frame-head to string:
    foreach (array_keys($frameHead) as $i) {
        $frameHead[$i] = chr($frameHead[$i]);
    }
    if ($masked === true) {
        // generate a random mask:
        $mask = array();
        for ($i = 0; $i < 4; $i++) {
            $mask[$i] = chr(rand(0, 255));
        }
        $frameHead = array_merge($frameHead, $mask);
    }
    $frame = implode('', $frameHead);
    // append payload to frame:
    for ($i = 0; $i < $payloadLength; $i++) {
        $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
    }
    return $frame;
}
