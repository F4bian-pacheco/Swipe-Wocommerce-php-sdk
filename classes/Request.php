<?php  
namespace Swipe\lib;
use Swipe\lib\Operacion;

class Request extends Operacion{
    public $customer_email; //string
    public $url_complete; //string
    public $url_cancel; //string
    public $url_callback; //string
    public $shop_country; //string
    public $session_id; //string
}