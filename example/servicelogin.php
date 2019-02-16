<?php
include('../vendor/autoload.php');
include('../src/Client.php');
include('../src/ServiceBase.php');
include('../src/Exceptions/CredentialException.php');

include('./exampleservice.php');


use Chez14\Desso;
$client = new Desso\Client();
$client->setCredential("uname", "password");

$client->login();

$studentportal = new StudentPortal();

if($client->serviceLogin($studentportal)) {
    echo "Student Portal Berhasil login.";
} else {
    echo "Student Portal GAGAL";
}