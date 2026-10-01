<?php

namespace App\Helper;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Response;
use Validator;

trait ApiResponseHelper
{
    protected Request $request;
    protected array $body;

    public function setData($data)
    {
        $this->body['data'] = $data;
        return $this;
    }


    public function setError($error)
    {
        $this->body['status'] = 'error';
        $this->body['message'] = $error;
        return $this;
    }

    public function setSuccess($message)
    {
        $this->body['status'] = 'success';
        $this->body['message'] = $message;
        return $this;
    }
    public function setMessage($message)
    {
        $this->body['message'] = $message;
        return $this;
    }

    public function setCode($code)
    {
        $this->body['status'] = $code;
        return $this;
    }


    public function send()
    {
        return response()->json($this->body, $this->body['status']);
    }

    public function json($code, $data, $message = '')
    {
        $this->setCode($code);
        $this->setData($data);
        $this->setMessage($message);
        return response()->json($this->body, $this->body['status']);
    }

    public function message($code, $message)
    {
        $this->setCode($code);
        $this->setMessage($message);
        return response()->json($this->body, $this->body['status']);
    }

    public function error($code, $data)
    {
        $this->setCode($code);
        $this->setError($data);
        return response()->json($this->body, $this->body['status']);
    }

    public function sendCollection($collection, $code): JsonResponse
    {

        return response()->json($collection, 200);
    }

    // public function validate($inputs, $rules): \Illuminate\Validation\Validator
    // {
    //     return Validator::make($inputs, $rules);
    // }
}
