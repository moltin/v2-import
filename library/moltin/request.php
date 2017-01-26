<?php

namespace Moltin;

class Request
{
    public $url;
    public $method;
    public $code;
    public $data;
    public $time;
    public $header;

    protected $curl;
    protected $options = array();

    public function setup($url, $method, $post = [], $files = [], $token = null)
    {
        // Variables
        $headers = array('Content-Type: application/json');
        $this->curl = curl_init();
        $this->url = $url;
        $this->method = $method;

        $this->options = array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 40
        );

        if ('POST' == $method) {
            $this->options[CURLOPT_POST] = true;
        }

        // Add post
        if (!empty($post) or !empty($files)) {
            $this->data = $this->toFormattedPostData($post, $files);
            if ( ! empty($files) ) { unset($headers[0]); }
            $this->options[CURLOPT_POSTFIELDS] = $this->data;
        }

        // Add auth header
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer '.$token;
        }

        // Add currency header
        if (isset($_SESSION['currency']) and $_SESSION['currency'] !== null) {
            $headers[] = 'X-Currency: '.$_SESSION['currency'];
        }

        // Add language header
        if (isset($_SESSION['language']) and $_SESSION['language'] !== null) {
            $headers[] = 'X-Language: '.$_SESSION['language'];
        }

        // Add session header
        $headers[] = 'X-Moltin-Session: '.session_id();

        // Set headers
        $this->options[CURLOPT_HTTPHEADER] = $headers;
    }

    /**
     * Recursive function that will generate an inline array to be send to the API
     *
     * @param  array  $value Array of keys/values to be processed
     * @param  string $key
     * @param  string $index Field key e.g. categories, orders
     * @return array  Array with all the resultant keys/values
     */
    protected function generateInlineArray($value, $key = '', $index = '') {
        if (is_array($value)) {
            $result = array();
            foreach($value as $k => $v) {
                $tmp = $this->generateInlineArray($v, $k, $index);
                if(isset($tmp['index']) && isset($tmp['value'])) {
                    // processing simple case
                    $result[$index . (!empty($key) ? '['.$key.']' : '') . '['.$tmp['index'].']'] = $tmp['value'];
                } else {
                    // use simple case to process complex case
                    $result = array_merge($result, $tmp);
                }
            }
            return $result;
        } else {
            // base case, no recursive call
            return array(
                'index' => $key,
                'value' => $value
            );
        }
    }

    public function make()
    {
        // Make request
        curl_setopt_array($this->curl, $this->options);
        $result = curl_exec($this->curl);
        $this->code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->time = curl_getinfo($this->curl, CURLINFO_TOTAL_TIME);
        $this->header = curl_getinfo($this->curl, CURLINFO_HEADER_OUT);

        /*echo '<pre>';
            echo '<em>' . $this->time . 'ms</em> <strong>' . $this->code . '</strong> - ' . $this->method . ' ' . $this->url . '<br />';
            var_dump($result);
        echo '</pre>';*/

        return $result;
    }

    /**
     * Properly format an array of data, and optionally $files,
     * to send with the request in a JSON format.
     *
     * @param $post array
     * @param $files array
     * @return string
     */
    protected function toFormattedPostData(array $post, array $files = [])
    {
        // Merge in files
        foreach ($files as $key => $data) {
            if (!isset($post[$key]) and strlen($data['tmp_name']) > 0) {
                $post[$key] = new \CurlFile($data['tmp_name'], $data['type'], $data['name']);
            }
        }

        // Files fix
        if ( ! empty($files) ) {            
            foreach ($post as $key => $value) {
                if (is_array($value)) {
                    $post = array_merge($post, $this->generateInlineArray($value, '', $key));
                    unset($post[$key]);
                }
            }

            return $post;
        }

        // Auth fix: no json
        if ( substr($this->url, -12) === 'access_token' ) {
            return $post;
        }

        return json_encode(array('data' => $post));
    }

    protected function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
}
