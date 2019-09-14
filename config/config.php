<?php
/**
 * Configuration
 */
class Config
{

    public function __construct()
    {
        # Initialized successfully
        date_default_timezone_set('Asia/Calcutta');
    }

    public function DBDetails()
    {
        # code...
        $details = array(
            'ServerName'     => 'localhost',
            'ConnectionInfo' => array(
                "Database" => "smartpro",
                "UID"      => "raja",
                "PWD"      => "raja123",
            ),
        );

        return $details;
    }

    public function BasicPaths()
    {
        $paths = array(
            'baseurl' => 'http://' . $_SERVER['HTTP_HOST'] . '/smartpro/',
        );

        return $paths;
    }
}
