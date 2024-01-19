<?php  
namespace Swipe\lib;
use Swipe\lib\Operacion;

class Response extends Operacion{
    public $gateway_reference; //string
    public $result; //string
    public $timestamp; //string
    public $test; //boolean
}