<?php

namespace TaskApp\TaskManager\Storage;

use Illuminate\Support\Facades\Storage;
use TaskApp\TaskManager\Contracts\StorageDriverInterface;

class FileStorageDriver implements StorageDriverInterface
{
    /**
     * Base storage path for file storage
     *
     * @var string
     */
    protected $basePath;
    
    /**
     * Storage disk to use
     *
     * @var string
     */
    protected $disk;
    
    /**
     * Create a new file storage driver instance
     *
     * @param string $basePath
     * @param string $disk
     * @return void
     */
    public function __construct($basePath = 'taskmanager/storage', $disk = 'local')
    {
        $this->basePath = $basePath;
        $this->disk = $disk;
        
        // Ensure base directory exists
        if (!Storage::disk($this->disk)->exists($this->basePath)) {
            Storage::disk($this->disk)->makeDirectory($this->basePath);
        }
    }
    
    /**
     * Get full path for a key
     *
     * @param string $key
     * @return string
     */
    protected function getPath($key)
    {
        // Create directory for the key if it contains folders
        $keyParts = explode('/', $key);
        if (count($keyParts) > 1) {
            $directory = $this->basePath . '/' . implode('/', array_slice($keyParts, 0, -1));
            if (!Storage::disk($this->disk)->exists($directory)) {
                Storage::disk($this->disk)->makeDirectory($directory);
            }
        }
        
        return $this->basePath . '/' . $key . '.json';
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $path = $this->getPath($key);
        
        if (!Storage::disk($this->disk)->exists($path)) {
            return $default;
        }
        
        try {
            $content = Storage::disk($this->disk)->get($path);
            $data = json_decode($content, true);
            
            // Check if data is expired
            if (isset($data['_expires_at']) && $data['_expires_at'] < time()) {
                $this->delete($key);
                return $default;
            }
            
            return $data['_value'] ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $path = $this->getPath($key);
        
        $data = [
            '_key' => $key,
            '_value' => $value,
            '_created_at' => time(),
            '_updated_at' => time(),
        ];
        
        if ($ttl !== null) {
            $data['_expires_at'] = time() + $ttl;
        }
        
        try {
            Storage::disk($this->disk)->put($path, json_encode($data, JSON_PRETTY_PRINT));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $path = $this->getPath($key);
        
        if (Storage::disk($this->disk)->exists($path)) {
            try {
                Storage::disk($this->disk)->delete($path);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $path = $this->getPath($key);
        
        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }
        
        try {
            $content = Storage::disk($this->disk)->get($path);
            $data = json_decode($content, true);
            
            // Check if data is expired
            if (isset($data['_expires_at']) && $data['_expires_at'] < time()) {
                $this->delete($key);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        try {
            $directories = Storage::disk($this->disk)->directories($this->basePath);
            $files = Storage::disk($this->disk)->files($this->basePath);
            
            foreach ($directories as $directory) {
                Storage::disk($this->disk)->deleteDirectory($directory);
            }
            
            foreach ($files as $file) {
                Storage::disk($this->disk)->delete($file);
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function keys($pattern)
    {
        $keys = [];
        $files = Storage::disk($this->disk)->allFiles($this->basePath);
        
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '/' . $pattern . '/';
        
        foreach ($files as $file) {
            if (substr($file, -5) !== '.json') {
                continue;
            }
            
            $key = substr($file, strlen($this->basePath) + 1, -5);
            
            if (preg_match($pattern, $key)) {
                // Check if expired
                try {
                    $content = Storage::disk($this->disk)->get($file);
                    $data = json_decode($content, true);
                    
                    if (!isset($data['_expires_at']) || $data['_expires_at'] >= time()) {
                        $keys[] = $key;
                    }
                } catch (\Exception $e) {
                    // Skip files that can't be read
                }
            }
        }
        
        return $keys;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys)
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $items, $ttl = null)
    {
        $success = true;
        
        foreach ($items as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }
        
        return $success;
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys)
    {
        $success = true;
        
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        
        return $success;
    }
} 