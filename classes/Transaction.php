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
            'DESARROLLO' => $_ENV['URL_DESARROLLO'],
            'PRODUCCION' => $_ENV['URL_PRODUCCION']
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
        $data["dte"] = $this->generateDTe($data);
        $response = $this->_initTransaction($data);
        return $response;
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

        // $data['x_signature'] = hash_hmac('sha256', $message, 'koGT79KqSy0lnwXnVxB8ARVLXBagFTa15AyYMH1dNLQAvdAOq9DseDWIZiB3YtcawcLHRDbzMrNGUeUES0IXvO0ogNUkkmhG7BGPTpnw1ZZw3jatAElhoVMcCYjZP7Nt');
        $data['x_signature'] = hash_hmac('sha256', $message, $this->token_secret);
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


    private function generateDTe($data) {
        if ($data['dte_type'] === 48 || $data['dte_type'] === 33) {
            return [
                "net_amount" => $data['x_amount'],
                "exempt_amount" => 1,
                "type" => $data['dte_type']
            ];
        } else {
            return [];
        }
    }
    

    /**
     * Realiza el llamado a initTransaction
     * @param $request contiene los datos a enviar en la peticion
     */
    function _initTransaction($request)
    {
        // Dispara formulario POST
        $actionUrl = htmlspecialchars($this->urls[$this->environment]);

        try {
            $curl = curl_init();
            curl_setopt_array(
                $curl,
                array(
                    CURLOPT_URL             => $actionUrl,
                    CURLOPT_RETURNTRANSFER  => true,
                    CURLOPT_ENCODING        => "",
                    CURLOPT_MAXREDIRS       => 10,
                    CURLOPT_TIMEOUT         => 30,
                    CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST   => 'POST',
                    CURLOPT_HTTPHEADER      => array(
                        "Content-Type: application/json"
                    ),
                    CURLOPT_POSTFIELDS      => json_encode($request),
                )
            );
            $response = curl_exec($curl);
            error_log("\nResponse: " . $response);
            $err = curl_error($curl);
            curl_close($curl);
            if($err){
                return $err;
            }
            
            return $response;
        } catch (\Throwable $th) {
            //throw $th;
            return 'error';
            
        }
        return 'error';
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
