<?php

namespace Swipe\lib;


class Transaction
{
    public $environment; //string

    private $request; //Request
    private $token_secret; //string
    private $status = array(
        'COMPLETATA',
        'FALLIDA',
        'ANULADA',
        'PENDIENTE'
    );

    private $urls;

    public function setToken($token_secret)
    {
        $this->token_secret = $token_secret;
    }

    public function __construct($request = null)
    {
        $this->request = $request;
        $this->urls = array(
            'DESARROLLO' => getenv('URL_DESARROLLO'),
            'PRODUCCION' => getenv('URL_PRODUCCION')
        );
    }

    /**
     * Iniciar una transaccion
     * @param $request contiene los datos principales de la peticion
     */
    public function initTransaction(array $data)
    {
        // generar firma
        $data['x_signature'] = $this->obtenerFirma($data, $this->token_secret);
        $response = $this->_initTransaction($data);
    }

    /**
     * Genera firma de transaccion
     * @param $data contiene arreglo con los datos a enviar
     */
    public function generarFirma(array $data)
    {
        unset($data['x_signature']);

        ksort($data);

        $message = '';
        foreach ($data as $key => $value) {
            if ($key == 'x_session_id') continue;
            $message .= $key . $value;
        }

        echo "<pre>";
        echo $message;
        echo "</pre>";

        $data['x_signature'] = hash_hmac('sha256', $message, 'koGT79KqSy0lnwXnVxB8ARVLXBagFTa15AyYMH1dNLQAvdAOq9DseDWIZiB3YtcawcLHRDbzMrNGUeUES0IXvO0ogNUkkmhG7BGPTpnw1ZZw3jatAElhoVMcCYjZP7Nt');
    }

    public function obtenerFirma(array $datos, string $llaveSecreta)
    {
        ksort($datos);
        $firmar = '';
        foreach ($datos as $llave => $valor) {
            if ($this->startsWith($llave, 'x_')) {
                $firmar .= $llave . $valor;
            }
        }
        // echo "<pre>";
        //     echo $firmar;
        // echo "</pre>";

        return hash_hmac("sha256", $firmar, $llaveSecreta);
    }

    public function startsWith($cadena, $prefijo)
    {
        return strpos($cadena, $prefijo) === 0;
    }

    /**
     * Valida firma y monto de response
     * @param $data contiene los datos con los cuales se genera la firma
     */
    public function validate($data)
    {
        /* Si no tiene firma se devuleve como error*/
        if (empty($data['x_signature'])) {
            return false;
        }

        $signature = $data['x_signature'];

        /*Se genera la firma*/
        // $this->generarFirma($data);


        return  $data['x_signature'] == $this->obtenerFirma($data, $this->token_secret);;
    }

    /**
     * Realiza el llamado a initTransaction
     * @param $request contiene los datos a enviar en la peticion
     */
    function _initTransaction($request)
    {
        // $this->urls[$this->environment] = 'https://core.payment.haulmer.com/api/v1/payment';

        // echo "<pre>";
        //     var_dump($this->urls[$this->environment]);
        //     var_dump($request);
        // echo "</pre>";
        // exit;

        // Dispara formulario POST
        $html = '';

        $html .= '<html>';
        $html .= '  <head>  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script></head>';
        $html .= '  <body>';
        $html .= '    <form name="requesstForm" id="requestForm" action=' . $this->urls[$this->environment] . ' method="POST">';
        foreach ($request as $key => $value) {
            $html .= '    <input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        $html .= '    </form>';
        $html .= '    <script type="text/javascript">';
        $html .= '      $(document).ready(function () {';
        $html .= '        $("#requestForm").submit(); ';
        $html .= '      });';
        $html .= '    </script>';
        $html .= '  </body>';
        $html .= '</html>';

        echo $html;
    }
    /**
     * Funcion que recibe la respuesta de la peticion
     */
    public function response($response)
    {
        return $response;
        // if($this->validate($request, $response)){
        //   return $response;
        // } else{
        //   $error = array(
        //     'Error'  => 'Transacción ' . $this->status[1],
        //     'Detail' => 'Error de validación de firma'
        //   );
        //   return $error;
        // }
    }
}
