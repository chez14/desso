<?php
namespace Chez14\Desso\Exceptions;

class CredentialException extends \Exception {
    public function __construct($message="", $code=0){
        parent::__construct($message, $code);
    }
}