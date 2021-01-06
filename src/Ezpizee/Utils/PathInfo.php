<?php

namespace Ezpizee\Utils;

final class PathInfo
{
    private $http = 'http://';
    private $https = 'https://';
    private $uri;
    private $selectors = array();
    private $parts = array();
    private $nodeName = '';
    private $file = '';
    private $fileFullName = '';
    private $ext = '';
    private $selectorString = '';
    private $path = '';
    private $pathFullName = '';
    private $minify = false;
    private $queryString = '';
    private $queryParams = array();
    private $schema = '';
    private $host = '';
    private $port = '80';
    private $pathSfx = '';
    private $allowedExtensions = array('php', 'html', 'htm');
    private $selectorsToParams = null;

    public function __construct(string $q = '')
    {
        if (!$q) {
            $q = $_SERVER['REQUEST_URI'];
            if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
                $this->schema = $this->https;
            }
            else {
                $this->schema = $this->http;
            }
            $this->host = $_SERVER['HTTP_HOST'];
            $this->port = $_SERVER['SERVER_PORT'];
        }
        else {
            if ($this->isExternal($q)) {
                if (StringUtil::startsWith($q, $this->https)) {
                    $this->schema = $this->https;
                }
                else {
                    $this->schema = $this->http;
                }
                $str = str_replace(array($this->http, $this->https), '', $q);
                $parts = explode('/', $str);
                if (StringUtil::contains($parts[0], ':')) {
                    $parts = explode(':', $parts[0]);
                    $this->port = end($parts);
                }
                $this->host = $parts[0];
            }
        }
        if (StringUtil::contains($q, '?')) {
            $parts = explode('?', $q);
            $this->queryString = end($parts);
            $params = array();
            parse_str($this->queryString, $params);
            $this->queryParams = new ListModel($params);
            $this->uri = trim(str_replace('?' . $this->queryString, '', $q), '\'/');
        }
        else {
            $this->queryParams = new ListModel(array());
            $this->uri = trim($q, '\'/');
        }
        $this->parts = new ListModel(explode('/', $this->uri));
        $this->ext = pathinfo($this->uri, PATHINFO_EXTENSION);
        if (!$this->ext || strlen($this->ext) > 7) {
            $this->ext = 'html';
        }
        // set selectors
        $parts = explode('.', $this->parts->last());
        $this->selectors = array();
        unset($parts[sizeof($parts) - 1]);
        unset($parts[0]);
        if (sizeof($parts)) {
            foreach ($parts as $part) {
                $this->selectors[] = $part;
            }
        }
        $this->selectors = new ListModel($this->selectors);

        $this->selectorString = implode('.', $this->selectors->getAsArray());
        $pattern = (sizeof($this->selectors) ? '.' : '') . $this->selectorString . '.' . $this->ext;
        $this->nodeName = str_replace($pattern, '', $this->nodeName);
        $this->path = str_replace($pattern, '', $this->uri);
        $this->pathFullName = $this->ext ? str_replace('.' . $this->ext, '', $this->uri) : $this->uri;
        $this->file = $this->path . ($this->ext ? '.' : '') . $this->ext;
        $this->fileFullName = $this->path . ($this->selectorString ? '.' : '') . $this->selectorString . ($this->ext ? '.' : '') . $this->ext;

        $this->setMinify(in_array('min', $this->selectors->getAsArray()));
        $this->setPathSuffix();
    }

    private function isExternal($q)
    {
        return StringUtil::startsWith($q, $this->https) || StringUtil::startsWith($q, $this->http);
    }

    private function setPathSuffix()
    {
        $del = '';
        $parts = explode('/', $this->uri);
        foreach ($parts as $part) {
            $exp = explode('.', $part);
            if (in_array($exp[sizeof($exp) - 1], $this->allowedExtensions)) {
                $del = $part;
                break;
            }
        }
        if ($del) {
            $parts = explode($del . '/', $this->uri);
            if (sizeof($parts) === 2) {
                $this->path = $parts[0] . $del;
                $this->pathSfx = $parts[1];
            }
        }
    }

    public function getUri()
    : string
    {
        return $this->uri;
    }

    public function getNodeName()
    : string
    {
        return $this->nodeName;
    }

    public function getExtension()
    : string
    {
        return $this->ext;
    }

    public function getSelectorString()
    : string
    {
        return $this->selectorString;
    }

    public function getPath()
    : string
    {
        return $this->path;
    }

    public function getPathFullName()
    : string
    {
        return $this->pathFullName;
    }

    public function isMinify()
    : bool
    {
        return $this->minify;
    }

    public function setMinify(bool $flag)
    {
        $this->minify = $flag;
    }

    public function getFile()
    : string
    {
        return $this->file;
    }

    public function getFileFullName()
    : string
    {
        return $this->fileFullName;
    }

    public function toArray()
    {
        return [
            'uri'               => $this->uri,
            'selectors'         => $this->getSelectors()->getAsArray(),
            'selectorsAsParams' => $this->getSelectorsAsParams(),
            'parts'             => $this->getParts()->getAsArray(),
            'numSegments'       => $this->getNumSegments(),
            'nodeName'          => $this->nodeName,
            'ext'               => $this->ext,
            'selectorString'    => $this->selectorString,
            'path'              => $this->path,
            'pathFullName'      => $this->pathFullName,
            'minify'            => $this->minify,
            'file'              => $this->file,
            'fileFullName'      => $this->fileFullName,
            'queryString'       => $this->getQueryString(),
            'queryParams'       => $this->getQueryParams()->getAsArray(),
            'schema'            => $this->getSchema(),
            'host'              => $this->getHost(),
            'fullUrl'           => $this->getFullUrl(),
            'isHttps'           => $this->isHttps(),
            'pathSfx'           => $this->getPathSuffix()
        ];
    }

    public function getSelectors()
    : ListModel
    {
        return $this->selectors;
    }

    public function getSelectorsAsParams()
    : ListModel
    {
        if ($this->selectorsToParams === null) {
            $selectorsToParams = [];
            if ($this->selectors->hasElement()) {
                foreach ($this->selectors->getAsArray() as $selector) {
                    $arr = explode(':', $selector);
                    if (sizeof($arr) > 2) {
                        $selectorsToParams[$arr[0]] = [];
                        for ($j = 1; $j < sizeof($arr); $j++) {
                            $selectorsToParams[$arr[0]][] = $arr[$j];
                        }
                    }
                    else if (sizeof($arr) > 1) {
                        $selectorsToParams[$arr[0]] = $arr[1];
                    }
                    else {
                        $selectorsToParams[$arr[0]] = '';
                    }
                }
            }
            $this->selectorsToParams = new ListModel($selectorsToParams);
        }
        return $this->selectorsToParams;
    }

    public function getParts()
    : ListModel
    {
        return $this->parts;
    }

    public function getNumSegments()
    : int
    {
        return $this->parts->count();
    }

    public function getQueryString()
    : string
    {
        return $this->queryString;
    }

    public function getQueryParams()
    : ListModel
    {
        return $this->queryParams;
    }

    public function getSchema()
    : string
    {
        return $this->schema;
    }

    public function getHost()
    : string
    {
        return $this->host;
    }

    public function getFullUrl()
    : string
    {
        return $this->schema . $this->host . '/' . $this->uri . ($this->queryString ? '?' . $this->queryString : '');
    }

    public function isHttps()
    : bool
    {
        return $this->schema === $this->https;
    }

    public function getPathSuffix()
    : string
    {
        return $this->pathSfx;
    }
}
