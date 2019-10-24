<?php

$ip = '192.168.43.1';  //where is the websocket server
$port = 81;
$local = "http://localhost";  //url where this script run

$datas = $_POST['datas'];
//$datas = '{"1":{"color":{"r":255,"g":255,"b":255}},"2":{"color":{"r":255,"g":255,"b":255}},"3":{"color":{"r":255,"g":255,"b":255}},"4":{"color":{"r":255,"g":255,"b":255}},"5":{"color":{"r":255,"g":255,"b":255}},"6":{"color":{"r":255,"g":255,"b":255}},"7":{"color":{"r":255,"g":255,"b":255}},"8":{"color":{"r":255,"g":255,"b":255}},"9":{"color":{"r":255,"g":255,"b":255}},"10":{"color":{"r":255,"g":255,"b":255}},"11":{"color":{"r":255,"g":255,"b":255}},"12":{"color":{"r":255,"g":255,"b":255}},"13":{"color":{"r":255,"g":255,"b":255}},"14":{"color":{"r":255,"g":255,"b":255}},"15":{"color":{"r":255,"g":255,"b":255}},"16":{"color":{"r":255,"g":255,"b":255}},"17":{"color":{"r":255,"g":255,"b":255}},"18":{"color":{"r":255,"g":255,"b":255}},"19":{"color":{"r":255,"g":255,"b":255}},"20":{"color":{"r":255,"g":255,"b":255}},"21":{"color":{"r":255,"g":255,"b":255}},"22":{"color":{"r":255,"g":255,"b":255}},"23":{"color":{"r":255,"g":255,"b":255}},"24":{"color":{"r":255,"g":255,"b":255}},"25":{"color":{"r":255,"g":255,"b":255}},"26":{"color":{"r":255,"g":255,"b":255}},"27":{"color":{"r":255,"g":255,"b":255}},"28":{"color":{"r":255,"g":255,"b":255}},"29":{"color":{"r":255,"g":255,"b":255}},"30":{"color":{"r":255,"g":255,"b":255}},"31":{"color":{"r":255,"g":255,"b":255}},"32":{"color":{"r":255,"g":255,"b":255}},"33":{"color":{"r":255,"g":255,"b":255}},"34":{"color":{"r":255,"g":255,"b":255}},"35":{"color":{"r":255,"g":255,"b":255}},"36":{"color":{"r":255,"g":255,"b":255}},"37":{"color":{"r":255,"g":255,"b":255}},"38":{"color":{"r":255,"g":255,"b":255}},"39":{"color":{"r":255,"g":255,"b":255}},"40":{"color":{"r":255,"g":255,"b":255}},"41":{"color":{"r":255,"g":255,"b":255}},"42":{"color":{"r":255,"g":255,"b":255}},"43":{"color":{"r":255,"g":255,"b":255}},"44":{"color":{"r":255,"g":255,"b":255}},"45":{"color":{"r":255,"g":255,"b":255}},"46":{"color":{"r":255,"g":255,"b":255}},"47":{"color":{"r":255,"g":255,"b":255}},"48":{"color":{"r":255,"g":255,"b":0}},"49":{"color":{"r":255,"g":255,"b":255}},"50":{"color":{"r":255,"g":255,"b":255}}}';
file_put_contents('last.txt', "datas: " . $datas . "\n", FILE_APPEND);
$rows = splitDatasIntoArray($datas);
foreach ($rows as $row) {
    $table = explode(":", $row);
    $num = trim($table[0], '"');
    file_put_contents('last.txt', "num: " . $num . "\n", FILE_APPEND);
    $color = getColorFromTable($table);
    file_put_contents('last.txt', "color: " . $color . "\n", FILE_APPEND);
    $host = getHostFromNum($ip, $num);
    file_put_contents('last.txt', "host: " . $host . "\n", FILE_APPEND);
    $head = "GET / HTTP/1.1" . "\r\n" .
    "Upgrade: WebSocket" . "\r\n" .
    "Connection: Upgrade" . "\r\n" .
    "Origin: $local" . "\r\n" .
    "Host: $host" . "\r\n" .
    "Sec-WebSocket-Version: 13" . "\r\n" .
    "Sec-WebSocket-Key: asdasdaas76da7sd6asd6as7d" . "\r\n" .
    "Content-Length: " . strlen($color) . "\r\n" . "\r\n";

    $sock = fsockopen($host, $port, $errno, $errstr, 2);
    fwrite($sock, $head) or die('error:' . $errno . ':' . $errstr);
    $headers = fread($sock, 2000);
    fwrite($sock, hybi10encode($color)) or die('error:' . $errno . ':' . $errstr);
    $wsdata = fread($sock, 2000);
    fclose($sock);
}
header('Location: index.php?saved=true&language=' . $_POST['language']);

function splitDatasIntoArray($datas)
{
    $line = substr($datas, 1);
    $line = substr($line, 0, -1);
    $line = str_replace("}},", "}}/", $line);
    $items = explode("/", $line);
    return $items;
}

function getColorFromTable($table)
{
    $red = $table[3];
    $green = explode(',', $table[4])[0];
    $blue = explode(',', $table[5])[0];
    $color = '*' . toHex($green) . toHex($red) . toHex($blue);
    return $color;
}

function getHostFromNum($ip, $num)
{
    if (strlen($num) < 2) {
        $host = $ip . "0" . $num;
    } else {
        $host = $ip . $num;
    }
    return $host;
}

function toHex($color)
{
    $val = dechex((int) $color);
    if (strlen($val) < 2) {
        $hex = "0" . $val;
    } else {
        $hex = $val;
    }
    return $hex;
}

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
