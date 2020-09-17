<?php

namespace App\Supports;

use Illuminate\Support\Facades\Storage;

trait MessageClass
{

    /** called when api response sending
     * @param null $returnArray
     * @param $returnMessage
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendBadRequest($returnArray = null, $returnMessage, $statusCode = RESPONSE_BAD_REQUEST)
    {
        $response = [ 
            'success' => false, 
            'status' => $statusCode, 
            'data' => null, 
            'message' => is_string($returnMessage) ? ucfirst(strtolower($returnMessage)) : $returnMessage 
        ]; 
        return response()->json($response); 
    }

    /**
     * @param null $returnArray
     * @param $returnMessage
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSuccessResponse($returnArray = null, $returnMessage, $statusCode = RESPONSE_CODE_SUCCESS)
    {
        $response = [
            'success' => true,
            'status' => $statusCode,
            'data' => $returnArray ?? null,
            'message' => ucfirst(strtolower($returnMessage))
        ];
        return response()->json($response);
    }

    /**
     * @param null $array
     * @param $returnMessage
     * @return array
     */
    public function makeResponse($array = null, $returnMessage)
    {
        return [
            'data' => $array,
            'message' => $returnMessage ?? '',
            'flag' => true
        ];
    }

    /** called for inner functions only
     * @param null $array
     * @param $returnMessage
     * @return array
     */
    public function makeError($array = null, $returnMessage)
    {
        return [
            'flag' => false,    
            'data' => $array ?? null,
            'message' => $returnMessage ?? '',
        ];
    }

    /**
     * storeRequestAndResponse => Create a file and store Incoming Request and Outgoing Response
     *
     * @param  mixed $data => saving data
     * @param  mixed $folderName => folder name
     * @param  mixed $fileName => file name 
     * @return void
     */
    public function storeRequestAndResponse($data, $folderName, $fileName = 'req-res.json')
    {
        Storage::put(
            'data/' . str_replace(' ', '', $folderName) . '/' . $fileName,
            json_encode($data)
            // json_encode($data, )
        );
    }
}
