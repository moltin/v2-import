<?php

namespace Moltin;

class Storage
{
    /**
     * Creates session or respawns previous instance, also created the default
     * addresses item if it doesn't already exist.
     *
     * @param array [$args] Optional array of arguments
     */
    public function __construct($args = [])
    {
        session_id() or session_start();

        // Create default item
        if (!isset($_SESSION['sdk'])) {
            $_SESSION['sdk'] = [];
        }
    }

    /**
     * Retrieves the given item by id.
     *
     * @param int $id The id to query by
     *
     * @return array|null
     */
    public function get($id)
    {
        // Not found
        if (!isset($_SESSION['sdk'][$id])) {
            return;
        }

        return $_SESSION['sdk'][$id];
    }

    /**
     * Inserts data or updates if id is provided.
     *
     * @param  int [$id] The id to update
     * @param array $data The data to insert/update
     *
     * @return $this
     */
    public function insertUpdate($id = null, $data)
    {
        $_SESSION['sdk'][$id] = $data;

        return $this;
    }

    /**
     * Removes an object with the given id from storage.
     *
     * @param int $id
     *
     * @return $this
     */
    public function remove($id)
    {
        unset($_SESSION['sdk'][$id]);

        return $this;
    }
}
