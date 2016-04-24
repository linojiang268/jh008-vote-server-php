<?php
namespace Jihe\Http\Responses;

use Jihe\Exceptions\ExceptionCode;

trait RespondsJson
{
    /**
     * json response
     *
     * @param object|array|string         $data      exception
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function json($data = null, $code = 0)
    {
        $response = [ 'code' =>  $code ];
    
        if (is_string($data)) {
            $response['message'] = $data;
        } else if (is_array($data)){
            $response = array_merge($response, $data);
        } else if (is_object($data)) {
            $response = array_merge($response, (array)$data);
        }

        return response()->json($response);
    }
    
    /**
     * json response for exception
     *
     * @param \Exception|array|string         $data      exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function jsonException($data, $code = ExceptionCode::GENERAL)
    {
        if ($data instanceof \Exception) {
            $code = $data->getCode() ?: $code;
            $response = [ 
                'code'      => $code,
                'message'   => $data->getMessage(),
            ];
    
            if (config('app.debug')) { // in case debug enabled
                $response['trace'] = $data->getTrace();
                $response['line'] = $data->getLine();
                $response['file'] = $data->getFile();
            }
    
            return $this->json($response, $code);
        }
    
        return $this->json($data, $code);
    }
}