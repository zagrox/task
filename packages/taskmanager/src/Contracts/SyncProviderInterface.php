<?php

namespace TaskManager\Contracts;

interface SyncProviderInterface
{
    /**
     * Get the name of the provider
     *
     * @return string
     */
    public function getName();
    
    /**
     * Get tasks from the external source
     *
     * @return array
     */
    public function getTasks();
    
    /**
     * Create a new task in the external source
     *
     * @param array $task Task data
     * @return string|int External ID of the created task
     */
    public function createTask(array $task);
    
    /**
     * Update an existing task in the external source
     *
     * @param string|int $externalId ID of the task in the external system
     * @param array $task Updated task data
     * @return bool
     */
    public function updateTask($externalId, array $task);
    
    /**
     * Delete a task in the external source
     *
     * @param string|int $externalId ID of the task in the external system
     * @return bool
     */
    public function deleteTask($externalId);
    
    /**
     * Check if the provider is properly configured
     *
     * @return bool
     */
    public function isConfigured();
} 