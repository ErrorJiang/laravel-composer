<?php

namespace Tanjiu\Http;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class MakesHttpRequests
{
    /**
     * 最大重试次数
     */
    const MAX_RETRIES = 2;

    /**
     * @var string
     */
    public $host;

    /**
     * @var array
     */
    public  $params;

    /**
     * @var string
     */
    public  $url;

    /**
     * @var string
     */
    public $method;

    /**
     * @var array
     */
    public $headers;


    /**
     * @var array
     */
    public $body = [];

    /**
     * @var string
     */
    public $request_time;

    /**
     * @var string
     */
    public $response_time;



    /**
     * set host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * set params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * set url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * set method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * set headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * set body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * set request time
     */
    public function setRequestTime()
    {
        $this->request_time = microtime(true) * 1000;
    }

    /**
     * set response time
     */
    public function setResponseTime()
    {
        $this->response_time = microtime(true) * 1000;
    }

    /**
     * set all
     */
    public function setAll($data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    public function makeRequest()
    {
        $this->setRequestTime();
        try {
            $response = $this->getHttpClient()->request($this->method, $this->url, $this->body);
        } catch (\Throwable $e) {
            $this->log([], $e->getMessage());
        }
        $this->setResponseTime();
        return $this->parseResponse($response);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     */
    protected function parseResponse($response)
    {
        $result = json_decode((string) $response->getBody(), true);
        $this->log($result);
        return $result;
    }

    /**
     * GuzzleRetry constructor.
     */
    public function getHttpClient()
    {
        // 创建 Handler
        $handlerStack = HandlerStack::create(new CurlHandler());
        // 创建重试中间件，指定决策者为 $this->retryDecider(),指定重试延迟为 $this->retryDelay()
        $handlerStack->push(Middleware::retry($this->retryDecider(), $this->retryDelay()));
        // 指定 handler
        return $this->httpClient ?: $this->httpClient = new Client([
            'handler' => $handlerStack,
            'base_uri' => $this->host,
            'http_errors' => false
        ]);
    }

    /**
     * retryDecider
     * 返回一个匿名函数, 匿名函数若返回false 表示不重试，反之则表示继续重试
     * @return Closure
     */
    protected function retryDecider(): callable
    {
        return function (
            $retries,
            Request $request,
            Response $response = null,
            RequestException $exception = null
        ) {
            // 超过最大重试次数，不再重试
            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            // 请求失败，继续重试
            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response) {
                // 如果请求有响应
                $httpStatusCode = $response->getStatusCode();
                // 200-300 successful 不需要重试
                if ($httpStatusCode >= 200 && $httpStatusCode <= 300) {
                    return false;
                }
                // 400-500 client error 客户端错误,不重试
                if ($httpStatusCode >= 400 && $httpStatusCode < 500) {
                    echo $httpStatusCode;
                    return true;
                }
                // 500 server error 服务端错误,重试
                if ($httpStatusCode >= 500) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * 返回一个匿名函数，该匿名函数返回下次重试的时间（毫秒）
     * @return Closure
     */
    protected function retryDelay(): callable
    {
        return function ($numberOfRetries) {
            return 1000 * $numberOfRetries;
        };
    }

    /**
     * 构建请求
     * @author jiangwen
     * @return Tanjiu\Http\HttpRequest
     */
    public function buildRequestBody($data): object
    {
        $this->dataValication($data);
        $this->setAll($data);
        if (strtolower($this->method) == 'get') {
            if (strpos($this->url, '?')) {
                $this->url .= http_build_query($this->params);
            } else {
                $this->url .= '?' . http_build_query($this->params);
            }
        }
        if (in_array(strtolower($this->method), ['post', 'delete', 'put', 'patch'])) {
            $this->headers['Content-Type'] = 'application/json';
            $this->body['json'] = $this->params;
        }
        if (strtolower($this->method) == 'post_form') {
            $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $this->body['form_params'] = $this->params;
        }
        if (strtolower($this->method) == 'post_multipart') {
            $this->headers['multipart'] = $this->params;
        }
        $this->body['headers'] = $this->headers;
        return $this;
    }

    /**
     * 记录请求日志及返回结果
     */
    public function log($return = [], $msg = 'SUCCESS')
    {
        $message = [
            'host' => $this->host,
            'url' => $this->url,
            'params' => $this->params,
            'method' => $this->method,
            'headers' => $this->headers,
            'return' => $return,
            'message' => $msg,
            'handle_time' => bcsub($this->response_time, $this->request_time, 3)
        ];
        Log::info('内部接口请求【' . $this->host . $this->url . '】', $message);
        return true;
    }

    /**
     * 数据验证
     * @param $data
     * @throws \Exception
     */
    public function dataValication($data)
    {
        if (!isset($data['url']) || empty($data['url'])) {
            throw new \Exception("HttpClient Error: Url不能为空", 500);
        } else {
            $this->url = $data['url'];
        }

        if (!isset($data['params']) || empty($data['params'])) {
            $this->params = [];
        } else {
            $this->params = $data['params'];
        }

        if (!isset($data['method']) || empty($data['method'])) {
            $this->method = "POST";
        } else {
            $this->method = $data['method'];
        }
    }
}
