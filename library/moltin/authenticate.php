<?php

namespace Moltin;

class Authenticate
{
    protected $data = [
        'token' => null,
        'refresh' => null,
        'expires' => null,
    ];

    public function authenticate($args, $parent)
    {
        // Validate
        if (($valid = $this->validate($args)) !== true) {
            throw new \Exception('Missing required params: '.implode(', ', $valid));
        }

        // Variables
        $url = $parent->url.'oauth/access_token';
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => $args['client_id'],
            'client_secret' => $args['client_secret'],
        ];

        // Make request
        $parent->request->setup($url, 'POST', $data);
        $result = $parent->request->make();

        // Check response
        $result = json_decode($result, true);

        // Fix backward compatibility with new services
        if (!isset($result['errors']) && isset($result['error'])) {
            $result['errors'] = $result['error'];
            unset($result['error']);
        }

        // Check JSON for errors
        if (isset($result['errors'])) {
            $exception = null;
            if (is_array($result['errors'])) {
                foreach($result['errors'] as $k => $v) {
                    if (isset($exception)) {
                        $exception = new \Exception($v[0], 0, $exception);
                    } else {
                        $exception = new \Exception($v[0]);
                    }
                }
            } else {
                $exception = $result['errors'];
            }
            throw new \Exception($exception);
        }

        // Set data
        $this->data['token'] = $result['access_token'];
        $this->data['refresh'] = null;
        $this->data['expires'] = $result['expires'];
    }

    public function refresh($args, $parent)
    {
        $this->authenticate($args, $parent);
    }

    public function get($key)
    {
        if (!isset($this->data[$key])) {
            return;
        }

        return $this->data[$key];
    }

    protected function validate($args)
    {
        // Variables
        $required = ['client_id', 'client_secret'];
        $keys = array_keys($args);
        $diff = array_diff($required, $keys);

        // Check for empty values
        foreach ($required as $key => $value) {
            if (strlen($value) <= 0) {
                $diff[] = $key;
            }
        }

        // Perform check
        return (empty($diff) ? true : $diff);
    }
}
