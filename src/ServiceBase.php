<?php
namespace Chez14\Desso;

abstract class ServiceBase {
    public abstract function get_service():String;
    
    public abstract function pre_login();

    public abstract function post_login(String $ticket);
}