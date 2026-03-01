<?php namespace Config;

class JWT
{
    public $key;
    public $key2;
    public $algorithm;
    public $expire;
    public $expire2;

    public function __construct()
    {
        $this->key = getenv('JWT1');
        $this->key2 = getenv('JWT2');
        $this->algorithm = getenv('hash') ?: 'HS256';
        $this->expire = (int) (getenv('expire1') ?: 3600);
        $this->expire2 = (int) (getenv('expire2') ?: 3600);
    }
}
