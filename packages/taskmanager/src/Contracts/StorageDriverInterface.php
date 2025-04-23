<?php

namespace TaskApp\TaskManager\Contracts;

interface StorageDriverInterface
{
    /**
     * Get an item from storage
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);
    
    /**
     * Store an item in storage
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function set($key, $value, $ttl = null);
    
    /**
     * Remove an item from storage
     *
     * @param string $key
     * @return bool
     */
    public function delete($key);
    
    /**
     * Check if an item exists in storage
     *
     * @param string $key
     * @return bool
     */
    public function has($key);
    
    /**
     * Clear all items from storage
     *
     * @return bool
     */
    public function clear();
    
    /**
     * Get all keys matching a pattern
     *
     * @param string $pattern
     * @return array
     */
    public function keys($pattern);
    
    /**
     * Get multiple items from storage
     *
     * @param array $keys
     * @return array
     */
    public function getMultiple(array $keys);
    
    /**
     * Store multiple items in storage
     *
     * @param array $items
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function setMultiple(array $items, $ttl = null);
    
    /**
     * Remove multiple items from storage
     *
     * @param array $keys
     * @return bool
     */
    public function deleteMultiple(array $keys);
} 