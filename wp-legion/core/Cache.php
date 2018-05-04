<?php

/**
 * Helpers to work with Wordpress transient API
 * 
 * @example
 * $foo = Cache::get('foo', function () {
 *     // query DB or other complicated stuff...
 *     return $results;
 * }, 24 * 60 * 60);
 *
 * @example
 * function clearTransientCache() {
 *     Cache::clear();
 * }
 * // clear cache when a post is create or updated
 * add_action('save_post', 'clearTransientCache');
 */
class Cache {
    public static function clear()
    {
        // @see http://isabelcastillo.com/delete-all-transients-wordpress
        global $wpdb;

        $sql = 'DELETE FROM `' . $wpdb->options . '` WHERE `option_name` LIKE ("%\_transient\_%")';

        if ($wpdb->query($sql)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $uid - Unique identifier (default: null)
     * @return bool
     */
    public static function delete($uid = null)
    {
        if (!is_null($uid)) {
            // @see http://codex.wordpress.org/Function_Reference/delete_transient
            return delete_transient($uid);
        }

        return self::clear();
    }


    /**
     * @param string $uid - Unique identifier
     * @param $default - Callback or value if missing (default: null)
     * @param int $expiration - Expiration in second (default: 0)
     * @return mixed
     */
    public static function get($uid, $default = null, $expiration = 0)
    {
        // @see http://codex.wordpress.org/Function_Reference/get_transient
        $value = get_transient($uid);

        if ($value === false) {
            if (is_callable($default)) {
                $value = $default($uid);
            } else {
                $value = $default;
            }

            self::set($uid, $value, $expiration);
        }

        return $value;
    }

    /**
     * @param string $uid - Unique identifier
     * @param $value
     * @param int $expiration - Expiration in second (default: 0)
     * @return bool
     */
    public static function set($uid, $value, $expiration = 0)
    {
        // @see http://codex.wordpress.org/Function_Reference/set_transient
        return set_transient($uid, $value, $expiration);
    }
}
