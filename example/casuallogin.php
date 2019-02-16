<?php

include('../vendor/autoload.php');
include('../src/Client.php');
include('../src/Exceptions/CredentialException.php');

use Chez14\Desso;
$client = new Desso\Client();
$client->setCredential("uname", "password");

if($client->loginValidate()) {
    echo "Login sudah oke!";
} else {
    echo "Belum login";
}
echo "\n\n";

if($client->login()){
    echo "Login berhasil";
} else {
    echo "Login gagal";
}
echo "\n\n";

if($client->loginValidate()) {
    echo "Login sudah oke!";
} else {
    echo "Belum login";
}
echo "\n\n";

$client->cookieJarSave('texting.txt');

$client = new Desso\Client(["cookie"=>"texting.txt"]);

if($client->loginValidate()) {
    echo "Login sudah oke!";
} else {
    echo "Belum login";
}
echo "\n\n";
echo "\n";