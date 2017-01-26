<?php

namespace Moltin;

class Refresh
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
            'grant_type' => 'refresh_token',
            'client_id' => $args['client_id'],
            'client_secret' => $args['client_secret'],
            'refresh_token' => $args['refresh_token'],
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
        $this->data['refresh'] = $result['refresh_token'];
        $this->data['expires'] = $result['expires'];
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
        $required = ['client_id', 'client_secret', 'refresh_token'];
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
