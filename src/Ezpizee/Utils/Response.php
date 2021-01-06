<?php

namespace Ezpizee\Utils;

use JsonSerializable;
use RuntimeException;

class Response implements JsonSerializable
{
    private $method = '';
    private $endpoint = '';
    private $data = [
        'status'  => ResponseCodes::STATUS_SUCCESS,
        'code'    => ResponseCodes::CODE_SUCCESS,
        'message' => ResponseCodes::MESSAGE_SUCCESS,
        'data'    => []
    ];

    public function __construct(string $method, string $endpoint, string $buffer)
    {
        $this->method = $method;
        $this->endpoint = $endpoint;
        $this->data['data'] = new ListModel([]);

        if (EncodingUtil::isValidJSON($buffer)) {
            $data = json_decode(str_replace(["\n", "\r", "\t"], '', $buffer), true);
            foreach ($data as $k => $item) {
                if (isset($this->data[$k])) {
                    $this->data[$k] = $item;
                }
            }
            $this->data['data'] = new ListModel($this->data['data']);
        }
        else {
            throw new RuntimeException('JSON_STRING_REQUIRED_FOR_BUFFER', ResponseCodes::CODE_ERROR_INTERNAL_SERVER);
        }
    }

    public function getStatus()
    : string
    {
        return $this->data['status'];
    }

    public function getMessage()
    : string
    {
        return $this->data['message'];
    }

    public function getData()
    : ListModel
    {
        if (is_null($this->data['data'])) {
            return new ListModel([]);
        }
        else if ($this->data['data'] instanceof ListModel) {
            if ($this->data['data']->has(0) && $this->data['data']->get(0) === null || $this->data['data']->get(0) === 'null') {
                return new ListModel([]);
            }
        }
        return $this->data['data'];
    }

    public function isError()
    : bool
    {
        return $this->getCode() !== 200;
    }

    public function getCode()
    : int
    {
        return (int)($this->data['code'] . '');
    }

    public function jsonSerialize()
    {
        return ['method' => $this->method, 'endpoint' => $this->endpoint, 'data' => $this->data];
    }

    public function __toString()
    {
        return json_encode(['method' => $this->method, 'endpoint' => $this->endpoint, 'data' => $this->data]);
    }
}
