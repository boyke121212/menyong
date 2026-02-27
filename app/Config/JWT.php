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
        $this->key = getenv('Brabsus1');
        $this->key2 = getenv('Brabsus2');
        $this->algorithm = getenv('Brabsusalgo') ?: 'HS256';
        $this->expire = getenv('Brabsus1exp') ?: 3600;
        $this->expire2 = getenv('Brabsus2exp') ?: 3600;
    }
}
