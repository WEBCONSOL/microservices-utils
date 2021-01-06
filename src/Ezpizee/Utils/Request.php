<?php

namespace Ezpizee\Utils;

class Request
{
    protected static $data;
    private $requestData = [];
    private $isAjax = false;
    private $isFormSubmission = false;
    private $opts = [];

    /**
     * @var \Slim\Http\Request
     */
    private $slimRequest;

    public function __construct(array $opts = array())
    {
        $this->opts = $opts;
        $this->isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' : false;
        self::$data['method'] = strtoupper($_SERVER["REQUEST_METHOD"]);
        self::$data['isHttps'] = (int)$_SERVER['SERVER_PORT'] === 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === "https") || isset($_SERVER['HTTP_X_FORWARDED_SSL']) || isset($_SERVER['HTTPS']) ? true : false;
        self::$data['pathInfo'] = new PathInfo($_SERVER["REQUEST_URI"]);
        self::$data['referer'] = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "";
        $this->checkIfTheRequestIsFormSubmission();
    }

    private function checkIfTheRequestIsFormSubmission()
    : void
    {
        self::$data['header'] = getallheaders();
        if (empty(self::$data['header'])) {
            if (!empty($this->slimRequest)) {
                self::$data['header'] = $this->slimRequest->getHeaders();
            }
            else {
                self::$data['header'] = [];
            }
        }
        $obj = $this->getHeaderParam('Access-Control-Request-Headers');
        if (!empty($obj)) {
            self::$data['header']['headerParamKeys'] = is_array($obj) || is_object($obj) ? json_encode($obj) : $obj;
        }
        else {
            self::$data['header']['headerParamKeys'] = implode(',', array_keys(self::$data['header']));
        }

        if ($this->method() === "POST") {
            if ((is_array($_POST) && !empty($_POST)) || (isset($_FILES) && is_array($_FILES) && !empty($_FILES))) {
                $this->isFormSubmission = true;
                $this->requestData = $_POST;
            }
            else {
                $requestBody = file_get_contents("php://input");
                if ($requestBody) {
                    if (EncodingUtil::isValidJSON($requestBody)) {
                        $this->requestData = json_decode($requestBody, true);
                    }
                    else {
                        parse_str($requestBody, $this->requestData);
                    }

                    $arrKeys = array_keys($this->requestData);

                    if (isset($arrKeys[0]) && strpos($arrKeys[0], '------') !== false &&
                        (strpos($arrKeys[0], 'Content-Disposition:_form-data;_name') !== false ||
                            strpos($arrKeys[0], 'Content-Disposition:_attachment;_name') !== false)) {
                        $this->requestData = array();
                        $this->parseRawHttpRequest($this->requestData);
                    }
                    $this->isFormSubmission = true;
                }
            }

            if (!empty($_GET)) {
                $this->requestData = array_merge($this->requestData, $_GET);
            }
        }
        else if ($this->method() === "DELETE" || $this->method() === "PUT" || $this->method() === "PATCH") {
            $requestBody = file_get_contents("php://input");
            if ($requestBody) {
                if (EncodingUtil::isValidJSON($requestBody)) {
                    $this->requestData = json_decode($requestBody, true);
                }
                else {
                    parse_str($requestBody, $this->requestData);
                }
            }
            if (!empty($_GET)) {
                $this->requestData = array_merge($this->requestData, $_GET);
            }
        }
        else if ($this->method() === 'GET') {
            $this->requestData = is_array($_GET) && !empty($_GET) ? $_GET : [];
        }
    }

    public function getHeaderParam($param, $default = '')
    : string
    {
        $v = $default;
        if (!empty($this->slimRequest)) {
            $v = $this->slimRequest->getHeaderLine($param);
        }
        if (empty($v)) {
            $v = isset(self::$data['header'][$param]) ? self::$data['header'][$param] : $default;
        }
        return $v || strlen($v) ? $v : $default;
    }

    public function method()
    : string
    {
        return !empty($this->slimRequest) ? $this->slimRequest->getMethod() : self::$data['method'];
    }

    private function parseRawHttpRequest(array &$a_data)
    {
        // read incoming data
        $input = file_get_contents('php://input');

        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        if (isset($matches[1])) {
            $boundary = $matches[1];

            // split content by boundary and get rid of last -- element
            $a_blocks = preg_split("/-+$boundary/", $input);
            array_pop($a_blocks);

            // loop data blocks
            foreach ($a_blocks as $id => $block) {
                if (empty($block))
                    continue;

                // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

                // parse uploaded files
                if (strpos($block, 'application/octet-stream') !== false) {
                    // match "name", then everything after "stream" (optional) except for prepending newlines
                    preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
                } // parse all other fields
                else {
                    // match "name" and optional value in between newline sequences
                    preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                }
                $a_data[$matches[1]] = isset($matches[2]) ? $matches[2] : "";
            }
        }
    }

    public static function isTheSameOrigin(string $referer = '')
    : bool
    {
        if (!$referer) {
            $referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        }
        $host = self::getHost();
        if ($referer && $host) {
            $parts = explode('/', $referer);
            return isset($parts[2]) && $parts[2] === $host;
        }
        return false;
    }

    public static function getHost()
    : string
    {
        if (!isset(self::$data['host'])) {
            self::$data['host'] = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
        }
        return self::$data['host'];
    }

    public function setSlimRequest($request)
    {
        if (class_exists('\Slim\Http\Request', false)) {
            if ($request instanceof \Slim\Http\Request) {
                $this->slimRequest = $request;
            }
        }
    }

    public function setRequestParam($key, $val)
    : void
    {
        $this->requestData[$key] = $val;
    }

    public function hasRequestParam($param)
    : bool
    {
        return isset($this->requestData[$param]) ||
            (!empty($this->slimRequest) &&
                ($this->slimRequest->getParam($param) !== null || $this->slimRequest->getParsedBodyParam($param) !== null)
            );
    }

    public function hasHeaderParam($param)
    : bool
    {
        if (!empty($this->slimRequest)) {
            return $this->slimRequest->hasHeader($param);
        }
        else {
            return isset(self::$data['header'][$param]);
        }
    }

    public function getBearerToken()
    : string
    {
        $v = $this->getHeaderParam('Authorization');
        return !empty($v) ? str_replace("Bearer ", '', $v) : $v;
    }

    public function getBasicToken()
    : string
    {
        $v = $this->getHeaderParam('Authorization');
        return !empty($v) ? str_replace("Basic ", '', $v) : $v;
    }

    public function getFiles()
    : array
    {
        return isset($_FILES) && !empty($_FILES) ? $_FILES : [];
    }

    public function contentType()
    : string
    {
        return trim(explode(';', $this->getHeaderParam('Content-Type'))[0]);
    }

    public function isAjax()
    : bool
    {
        return $this->isAjax;
    }

    public function hasFormSubmission()
    : bool
    {
        return $this->method() === 'POST' && $this->isFormSubmission;
    }

    public function isHttps()
    : bool
    {
        return self::$data['isHttps'];
    }

    public function httpsSchema()
    : string
    {
        return 'https://';
    }

    public function httpSchema()
    : string
    {
        return 'http://';
    }

    public function getRequestParamAsArray($param, $default = [])
    : array
    {
        $data = $this->getRequestParam($param, $default);
        if (is_array($data)) {
            return $data;
        }
        else if (is_object($data)) {
            return json_decode(json_encode($data), true);
        }
        else if (EncodingUtil::isValidJSON($data)) {
            return json_decode($data, true);
        }
        return $default;
    }

    public function getRequestParam($param, $default = null, bool $asString = false)
    {
        $v = $default;
        if (isset($this->requestData[$param])) {
            $v = $this->requestData[$param];
        }
        else if (!empty($this->slimRequest)) {
            $v = $this->slimRequest->getParam($param, $default);
        }
        if ($asString && (is_array($v) || is_object($v))) {
            $v = json_encode($v);
        }
        else if (EncodingUtil::isValidJSON($v)) {
            return json_decode($v, true);
        }
        return $v;
    }

    public function getHeaderKeysAsString()
    : string
    {
        return self::$data['headerParamKeys'];
    }

    public function getRequestParamsAsArray()
    : array
    {
        return $this->getRequestObjectAsArray();
    }

    public function getRequestObjectAsArray()
    : array
    {
        return is_array($this->requestData) ? $this->requestData : [];
    }

    public function isPOST()
    : bool
    {
        return $this->method() === 'POST';
    }

    public function isGET()
    : bool
    {
        return $this->method() === 'GET';
    }

    public function isDELETE()
    : bool
    {
        return $this->method() === 'DELETE';
    }

    public function isPUT()
    : bool
    {
        return $this->method() === 'PUT';
    }

    public function pathInfo()
    : PathInfo
    {
        return self::$data['pathInfo'];
    }
}
