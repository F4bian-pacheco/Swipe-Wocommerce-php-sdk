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


    public function obtenerFirma(array $datos, string $llaveSecreta) // equivalente a generateTextToSign y generateSignature
    {
        ksort($datos);
        $firmar = '';
        foreach ($datos as $llave => $valor) {
            if ($this->startsWith($llave, 'x_')) {
                $firmar .= $llave . $valor;
            }
        }

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
        // validate monto
        if ($data['x_amount'] != $this->request->amount) {
            return false;
        }

        $signature = $data['x_signature'];

        return  $data['x_signature'] == $this->obtenerFirma($data, $this->token_secret);;
    }

    /**
     * Realiza el llamado a initTransaction
     * @param $request contiene los datos a enviar en la peticion
     */
    function _initTransaction($request)
    {

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
}
