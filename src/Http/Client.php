<?php

namespace Tanjiu\Http;

class Client extends MakesHttpRequests
{
    /**
     * 请求
     * @param $data array
     * data = [
     *      'host' => 'http://vhome.tanjiu.cn',
     *      'url' => '/rest/saleOrder/list',
     *      'params' => ['point_id' => 26950],
     *      'headers' => ['token' => 'xxxxxx'],
     *      'method' => 'post/post_form/post_multipart/get/delete/put/patch/get'
     * ]
     * 
     */
    public function request(array $data)
    {
        return $this->buildRequestBody($data)->makeRequest();
    }
}
