<?php

namespace Ezpizee\Utils;

use Slim\Http\Request as SlimRequest;

class Request
{
    protected static $data;
    private array $requestData = [];
    private bool $isAjax = false;
    private bool $isFormSubmission = false;
    private array $files = [];
    private string $schema_http = "http://";
    private string $schema_https = "https://";
    /** @var SlimRequest $slimRequest */
    private $slimRequest;

    public function __construct(array $opts = array())
    {
        if (isset($opts['request'])) {
            $this->setSlimRequest($opts['request']);
        }
        $this->cloudflareSSL();
        $this->isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' : false;
        self::$data['method'] = strtoupper($_SERVER["REQUEST_METHOD"]);
        self::$data['isHttps'] = (int)$_SERVER['SERVER_PORT'] === 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === "https") || isset($_SERVER['HTTP_X_FORWARDED_SSL']) || isset($_SERVER['HTTPS']);
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

        if (!empty($this->slimRequest) && class_exists('\Slim\Http\Request', false)) {
            $this->loadRequestDataFromSlimRequest();
        }
        else if ($this->method() === "POST") {
            if ((is_array($_POST) && !empty($_POST)) || (isset($_FILES) && is_array($_FILES) && !empty($_FILES))) {
                if (is_array($_POST) && !empty($_POST)) {
                    $this->requestData = $_POST;
                }
                if (is_array($_FILES) && !empty($_FILES)) {
                    $this->files = $_FILES;
                }
                $this->isFormSubmission = true;
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

    public function getFiles(string $key = '')
    : array
    {
        if (!empty($this->files)) {
            return !empty($key) ? (isset($this->files[$key]) ? $this->files[$key] : []) : $this->files;
        }
        else if (is_array($_FILES) && !empty($_FILES)) {
            return !empty($key) ? (isset($_FILES[$key]) ? $_FILES[$key] : []) : $_FILES;
        }
        return [];
    }

    public function getHeaderParam(string $param, $default = '')
    : string
    {
        if (isset(self::$data['header'][$param])) {
            $v = self::$data['header'][$param];
        }
        else if (isset(self::$data['header'][strtoupper($param)])) {
            $v = self::$data['header'][strtoupper($param)];
        }
        else if (isset(self::$data['header'][strtolower($param)])) {
            $v = self::$data['header'][strtolower($param)];
        }
        else if (!empty($this->slimRequest)) {
            $v = $this->slimRequest->getHeaderLine($param);
        }
        else {
            $v = $default;
        }
        return $v;
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

    public function originHost(): string {return $this->getHeaderParam('Origin');}

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

    public function protocol() {return $this->isHttps() ? $this->schema_https : $this->schema_http;}

    public function getUrl(): string {return $this->protocol() . $this->host() . '/' . ltrim($this->uri(), '/');}

    public function host(): string {return self::getHost();}

    public function hostOnly(): string {
        $arr = explode(':', $this->host());
        return $arr[0];
    }

    public function setSlimRequest($request)
    {
        if (class_exists('\Slim\Http\Request', false)) {
            if ($request instanceof \Slim\Http\Request) {
                $this->slimRequest = $request;
            }
        }
    }

    public function setRequestParam(string $key, $val)
    : void
    {
        $this->requestData[$key] = $val;
    }

    public function removeRequestParam(string $key)
    : void
    {
        if (isset($this->requestData[$key])) {
            unset($this->requestData[$key]);
        }
    }

    public function hasRequestParam(string $param)
    : bool
    {
        return isset($this->requestData[$param]) ||
            (!empty($this->slimRequest) &&
                ($this->slimRequest->getParam($param) !== null || $this->slimRequest->getParsedBodyParam($param) !== null)
            );
    }

    public function hasHeaderParam(string $param)
    : bool
    {
        if (isset(self::$data['header'][$param])) {
            return true;
        }
        else if (isset(self::$data['header'][strtoupper($param)])) {
            return true;
        }
        else if (isset(self::$data['header'][strtolower($param)])) {
            return true;
        }
        else if (!empty($this->slimRequest)) {
            return $this->slimRequest->hasHeader($param);
        }
        return false;
    }

    public function setHeaderParam(string $param, string $value): void {self::$data['header'][$param] = $value;}

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

    public function isHttps(): bool {return self::$data['isHttps'];}

    public function httpsSchema(): string {return $this->schema_https;}

    public function httpSchema(): string {return $this->schema_http;}

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

    public function getRequestParamAsInt($param, $default = '0'): int {return (int)$this->getRequestParam($param, $default, true);}
    public function getRequestParamAsFloat($param, $default = '0.00'): int {return (float)$this->getRequestParam($param, $default, true);}
    public function getRequestParamAsString($param, $default = ''): string {return $this->getRequestParam($param, $default, true);}
    public function getRequestParamAsBoolean($param, $default = 'false'): bool {return (bool)$this->getRequestParam($param, $default, true);}

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
        return isset(self::$data['header']['headerParamKeys']) ? self::$data['header']['headerParamKeys'] : '';
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

    public function isPOST(): bool {return $this->method() === 'POST';}

    public function isGET(): bool {return $this->method() === 'GET';}

    public function isDELETE(): bool {return $this->method() === 'DELETE';}

    public function isPUT(): bool {return $this->method() === 'PUT';}

    public function pathInfo()
    : PathInfo
    {
        return self::$data['pathInfo'];
    }

    public function uri() {return self::pathInfo()->getUri();}

    public function getHeaderCookies():
    array
    {
        $cookies = array();
        if (isset(self::$data['header']) && !empty(self::$data['header']) && isset(self::$data['header']['Cookie'])) {
            $headerCookies = explode('; ', self::$data['header']['Cookie']);
            foreach($headerCookies as $itm) {
                list($key, $val) = explode('=', $itm,2);
                $cookies[$key] = $val;
            }
        }
        return $cookies;
    }

    public function getRequestHeaders(): array {return isset(self::$data['header']) ? self::$data['header'] : getallheaders();}

    public static final function jsonStringToParamsBodyRequest(string &$jsonStr)
    : void
    {
        $arr = [];
        $obj = json_decode($jsonStr, true);
        foreach ($obj as $k=>$v) {
            $arr[] = $k.'="'.(is_array($v) ? json_encode($v) : $v).'"';
        }
        $jsonStr = implode('&amp;', $arr);
    }

    private function loadRequestDataFromSlimRequest()
    : void
    {
        if (empty($this->requestData) && !empty($this->slimRequest)) {
            if ($this->slimRequest instanceof SlimRequest) {
                $body = $this->slimRequest->getParsedBody();
                $this->requestData = !empty($body) ? $body : [];
                if ($this->slimRequest->getMediaType() === Constants::HEADER_CONTENT_TYPE_VALUE_MULTIPARTS) {
                    $this->files = !empty($_FILES) ? $_FILES : [];
                }
                if (empty($this->requestData)) {
                    $data = $this->slimRequest->getBody()->getContents();
                    if (!empty($data)) {
                        $this->requestData = json_decode($data, true);
                    }
                }
                if (is_object($this->requestData)) {
                    $this->requestData = json_decode(json_encode($this->requestData), true);
                }
            }
        }
    }

    public function getReferredUri(): string {
        if (self::hasReferer()) {
            $arr = explode('/', self::getReferredHost());
            return $arr[sizeof($arr)-1];
        }
        return '';
    }

    public function getReferredHost(): string {
        if (self::hasReferer()) {
            $arr = explode('/', self::getReferer());
            return isset($arr[2]) ? $arr[2] : '';
        }
        return '';
    }

    public function getReferer(): string {return self::hasReferer() ? self::$data['referer'] : '';}

    public function hasReferer(): bool {return !empty(self::$data['referer']);}

    public function getUserInfoAsUniqueId(): string {
        return md5(json_encode($this->getUserInfo()));
    }

    public function getUserInfo(): array
    {
        $referer = [];
        if ($this->hasReferer()) {
            $pathInfo = new PathInfo(self::$data['referer']);
            $referer = ['referer_host'=>$pathInfo->getHost(), 'referer_schema'=>$pathInfo->getSchema()];
        }
        return [
            'user_agent' => $this->getHeaderParam('User-Agent'),
            'user_ip' => $this->userIpAddr(),
            'referer' => $referer
        ];
    }

    private function userIpAddr(){
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    private function cloudflareSSL() {
        if (isset($_SERVER['HTTP_CF_VISITOR'])) {
            if (preg_match('/https/i', $_SERVER['HTTP_CF_VISITOR'])) {
                $_SERVER['HTTPS'] = 'On';
                $_SERVER['HTTP_X_FORWARDED_PORT'] = 443;
                $_SERVER['SERVER_PORT'] = 443;
            }
        }
    }
}
