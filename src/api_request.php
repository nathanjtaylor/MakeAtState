<?php

class ApiRequest {
    private $url;
    private $headers;
    private $get_args;
    private $post_args;
    private $response;
    private $error;

    public function __construct($url) {
        $this->url = $url;
        $this->headers = false;
        $this->get_args = false;
        $this->post_args = false;
        $this->response = false;
        $this->errmsg = "";
    }

    public function setHeader($val) {
        if (!$this->headers) {
            $this->headers = [];
        }
        $this->headers[] = $val;
    }

    public function setGetArg($key, $val) {
        if (!$this->get_args) {
            $this->get_args = [];
        }
        $this->get_args[$key] = $val;
    }

    public function setPostArg($key, $val) {
        if (!$this->post_args) {
            $this->post_args = [];
        }
        $this->post_args[$key] = $val;
    }

    public function call() {
        $options = [
            CURLOPT_HEADER         => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_RETURNTRANSFER => true,
        ];
        if ($this->post_args !== false) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($this->post_args);
            $this->setHeader('Content-Length: ' . strlen($options[CURLOPT_POSTFIELDS]));
        }
        if ($this->headers !== false) {
            $options[CURLOPT_HTTPHEADER] = $this->headers;
        }
        $query = "";
        if ($this->get_args !== false) {
            $query = "?" . http_build_query($this->get_args);
        }
        $options[CURLOPT_URL] = $this->url . $query;

        $handle = curl_init();
        curl_setopt_array($handle, $options);
        $this->response = curl_exec($handle);
        $this->errmsg = curl_error($handle);
        $info = curl_getinfo($handle);
        curl_close($handle);
        return ($this->response !== false);
    }

    public function content() {
        return ($this->response === false) ? null : $this->response;
    }

    public function json() {
        return ($this->response === false) ? null : json_decode($this->response);
    }

    public function error() {
        return $this->errmsg;
    }
}

