<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class AiTaskProcessingService
{
    protected $tasksFile;
    protected $aiEndpoint;
    protected $apiKey;
    
    public function __construct()
    {
        $this->tasksFile = base_path('project-management/tasks.json');
        $this->aiEndpoint = env('AI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions');
        $this->apiKey = env('AI_API_KEY');
    }
    
    /**
     * Process pending tasks automatically
     * 
     * @param int $limit Maximum number of tasks to process
     * @return array Information about processed tasks
     */
    public function processPendingTasks(int $limit = 5)
    {
        if (!File::exists($this->tasksFile)) {
            Log::error('Tasks file not found');
            throw new \Exception('Tasks file not found');
        }
        
        // Load tasks data
        $tasksData = json_decode(File::get($this->tasksFile), true);
        $tasks = $tasksData['tasks'] ?? [];
        
        // Filter pending tasks assigned to AI
        $pendingTasks = array_filter($tasks, function($task) {
            return $task['status'] === 'pending' && $task['assignee'] === 'ai';
        });
        
        if (empty($pendingTasks)) {
            Log::info('No pending AI tasks found');
            return ['processed' => 0, 'message' => 'No pending AI tasks found'];
        }
        
        // Sort tasks by priority
        usort($pendingTasks, function($a, $b) {
            $priorityOrder = [
                'critical' => 1,
                'high' => 2,
                'medium' => 3,
                'low' => 4
            ];
            
            $aPriority = $priorityOrder[$a['priority']] ?? 5;
            $bPriority = $priorityOrder[$b['priority']] ?? 5;
            
            return $aPriority <=> $bPriority;
        });
        
        // Limit the number of tasks to process
        $pendingTasks = array_slice($pendingTasks, 0, $limit);
        
        Log::info('Starting to process ' . count($pendingTasks) . ' pending AI tasks');
        
        $processed = 0;
        $results = [];
        
        // Process each task one by one
        foreach ($pendingTasks as $taskIndex => $task) {
            try {
                $result = $this->processTask($tasksData, $task);
                $results[] = [
                    'id' => $task['id'],
                    'title' => $task['title'],
                    'status' => 'completed',
                    'message' => 'Task processed successfully'
                ];
                $processed++;
            } catch (\Exception $e) {
                Log::error("Error processing task #{$task['id']}: " . $e->getMessage());
                $results[] = [
                    'id' => $task['id'],
                    'title' => $task['title'],
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
            
            // Save changes after each task to ensure progress is preserved
            File::put($this->tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        }
        
        return [
            'processed' => $processed,
            'message' => "Processed {$processed} tasks",
            'results' => $results
        ];
    }
    
    /**
     * Process a single task by its ID
     * 
     * @param int $taskId
     * @return array Task processing result
     */
    public function processTaskById(int $taskId)
    {
        if (!File::exists($this->tasksFile)) {
            Log::error('Tasks file not found');
            throw new \Exception('Tasks file not found');
        }
        
        // Load tasks data
        $tasksData = json_decode(File::get($this->tasksFile), true);
        $tasks = $tasksData['tasks'] ?? [];
        
        // Find the specific task
        $taskIndex = null;
        $task = null;
        
        foreach ($tasks as $index => $t) {
            if ($t['id'] == $taskId) {
                $taskIndex = $index;
                $task = $t;
                break;
            }
        }
        
        if ($task === null) {
            Log::error("Task #{$taskId} not found");
            throw new \Exception("Task #{$taskId} not found");
        }
        
        // Process the task
        return $this->processTask($tasksData, $task);
    }
    
    /**
     * Process a single task
     * 
     * @param array &$tasksData Reference to tasks data for updating
     * @param array $task Task to process
     * @return array Processing result
     */
    protected function processTask(array &$tasksData, array $task)
    {
        Log::info("Processing task #{$task['id']}: {$task['title']}");
        
        // Find the task index in the tasks array
        $taskIndex = null;
        foreach ($tasksData['tasks'] as $index => $t) {
            if ($t['id'] == $task['id']) {
                $taskIndex = $index;
                break;
            }
        }
        
        if ($taskIndex === null) {
            throw new \Exception("Task index not found for task #{$task['id']}");
        }
        
        try {
            // Mark task as in-progress
            $tasksData['tasks'][$taskIndex]['status'] = 'in-progress';
            $tasksData['tasks'][$taskIndex]['progress'] = 25;
            $tasksData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
            
            // Define the task (analyze and understand what needs to be done)
            $taskDefinition = $this->defineTask($task);
            
            // Update progress
            $tasksData['tasks'][$taskIndex]['progress'] = 50;
            $tasksData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
            
            // Add definition note
            $tasksData['tasks'][$taskIndex]['notes'][] = [
                'content' => "AI Task Definition:\n\n" . $taskDefinition,
                'timestamp' => Carbon::now()->toIso8601String()
            ];
            
            // Execute the task
            $result = $this->executeTask($task, $taskDefinition);
            
            // Update task with results
            $tasksData['tasks'][$taskIndex]['status'] = 'completed';
            $tasksData['tasks'][$taskIndex]['progress'] = 100;
            $tasksData['tasks'][$taskIndex]['actual_hours'] = $task['estimated_hours'] ?? 1; // Use estimated hours or default to 1
            $tasksData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
            $tasksData['tasks'][$taskIndex]['completed_at'] = Carbon::now()->toIso8601String();
            
            // Add result note
            $tasksData['tasks'][$taskIndex]['notes'][] = [
                'content' => "Task Results:\n\n" . $result,
                'timestamp' => Carbon::now()->toIso8601String()
            ];
            
            Log::info("Task #{$task['id']} completed successfully");
            
            return [
                'id' => $task['id'],
                'status' => 'completed',
                'definition' => $taskDefinition,
                'result' => $result
            ];
        } catch (\Exception $e) {
            // Handle errors
            $tasksData['tasks'][$taskIndex]['status'] = 'blocked';
            $tasksData['tasks'][$taskIndex]['progress'] = 0;
            $tasksData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
            
            // Add error note
            $tasksData['tasks'][$taskIndex]['notes'][] = [
                'content' => "Error during AI processing: " . $e->getMessage(),
                'timestamp' => Carbon::now()->toIso8601String()
            ];
            
            Log::error("Error processing task #{$task['id']}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Define what the task entails
     * 
     * @param array $task
     * @return string Task definition
     */
    protected function defineTask(array $task)
    {
        Log::info("Defining task #{$task['id']}");
        
        // In a real implementation, we would use AI to analyze and define the task
        // For now, we'll create a simulated definition based on the task properties
        
        $taskType = $this->determineTaskType($task);
        
        $definition = "# Task Definition: {$task['title']}\n\n";
        $definition .= "## Task Analysis\n";
        $definition .= "Priority: {$task['priority']}\n";
        $definition .= "Estimated Hours: {$task['estimated_hours']}\n";
        $definition .= "Type: {$taskType}\n\n";
        
        $definition .= "## Steps to Complete\n";
        
        // Generate steps based on task type
        $steps = $this->generateStepsForTask($task, $taskType);
        foreach ($steps as $index => $step) {
            $definition .= ($index + 1) . ". {$step}\n";
        }
        
        $definition .= "\n## Expected Outcomes\n";
        $definition .= "1. All requirements implemented successfully\n";
        $definition .= "2. Code changes documented\n";
        $definition .= "3. Any necessary tests created or updated\n";
        
        return $definition;
    }
    
    /**
     * Execute the defined task
     * 
     * @param array $task
     * @param string $taskDefinition
     * @return string Execution result
     */
    protected function executeTask(array $task, string $taskDefinition)
    {
        Log::info("Executing task #{$task['id']}");
        
        // In a real implementation, this would involve actual code generation and task execution
        // For now, we'll simulate results
        
        // Simulate processing time
        sleep(2);
        
        $result = "# Task Execution Results\n\n";
        $result .= "## Summary\n";
        $result .= "Task '{$task['title']}' has been completed successfully.\n\n";
        
        $result .= "## Implementation Details\n";
        
        // Generate fake implementation details
        $implementations = $this->simulateImplementation($task);
        foreach ($implementations as $impl) {
            $result .= "- {$impl}\n";
        }
        
        $result .= "\n## Code Changes\n";
        $result .= "```php\n";
        $result .= "// Sample code changes for {$task['title']}\n";
        $result .= "function process{$this->camelCase($task['title'])}() {\n";
        $result .= "    // Implementation\n";
        $result .= "    return 'Task completed successfully';\n";
        $result .= "}\n";
        $result .= "```\n\n";
        
        $result .= "## Testing\n";
        $result .= "All tests have passed successfully.\n\n";
        
        $result .= "## Recommendations\n";
        $result .= "1. Consider adding more comprehensive test coverage\n";
        $result .= "2. Review the implementation for potential optimizations\n";
        
        return $result;
    }
    
    /**
     * Determine the type of task based on its properties
     * 
     * @param array $task
     * @return string Task type
     */
    protected function determineTaskType(array $task)
    {
        $title = strtolower($task['title']);
        $description = strtolower($task['description']);
        
        if (strpos($title, 'implement') !== false || strpos($description, 'implement') !== false) {
            return 'Implementation';
        }
        
        if (strpos($title, 'fix') !== false || strpos($description, 'fix') !== false || 
            strpos($title, 'bug') !== false || strpos($description, 'bug') !== false) {
            return 'Bug Fix';
        }
        
        if (strpos($title, 'refactor') !== false || strpos($description, 'refactor') !== false) {
            return 'Refactoring';
        }
        
        if (strpos($title, 'test') !== false || strpos($description, 'test') !== false) {
            return 'Testing';
        }
        
        if (strpos($title, 'document') !== false || strpos($description, 'document') !== false) {
            return 'Documentation';
        }
        
        return 'Development';
    }
    
    /**
     * Generate steps for a task based on its type
     * 
     * @param array $task
     * @param string $taskType
     * @return array Steps
     */
    protected function generateStepsForTask(array $task, string $taskType)
    {
        $steps = [];
        
        switch ($taskType) {
            case 'Implementation':
                $steps = [
                    'Analyze requirements and scope',
                    'Design the solution architecture',
                    'Implement core functionality',
                    'Add supporting utilities and helpers',
                    'Create tests to validate implementation',
                    'Document the implementation',
                ];
                break;
                
            case 'Bug Fix':
                $steps = [
                    'Reproduce the issue',
                    'Identify the root cause',
                    'Develop a fix',
                    'Test the fix in isolation',
                    'Ensure no regressions were introduced',
                    'Document the fix and its implications',
                ];
                break;
                
            case 'Refactoring':
                $steps = [
                    'Analyze the current code structure',
                    'Identify parts that need improvement',
                    'Plan the refactoring approach',
                    'Implement changes incrementally',
                    'Maintain test coverage',
                    'Document architectural changes',
                ];
                break;
                
            case 'Testing':
                $steps = [
                    'Identify test requirements',
                    'Design test cases',
                    'Implement test suite',
                    'Run tests and verify coverage',
                    'Document testing approach',
                ];
                break;
                
            case 'Documentation':
                $steps = [
                    'Identify documentation needs',
                    'Gather necessary information',
                    'Create documentation structure',
                    'Write documentation content',
                    'Review and refine documentation',
                ];
                break;
                
            default:
                $steps = [
                    'Analyze task requirements',
                    'Plan implementation approach',
                    'Execute development work',
                    'Test functionality',
                    'Document changes',
                ];
                break;
        }
        
        return $steps;
    }
    
    /**
     * Simulate implementation details for task result
     * 
     * @param array $task
     * @return array Implementation details
     */
    protected function simulateImplementation(array $task)
    {
        $implementations = [
            "Created new service class for {$task['title']}",
            "Implemented core functionality according to specifications",
            "Added error handling and logging",
            "Created tests to verify functionality",
            "Updated documentation to reflect changes",
        ];
        
        return $implementations;
    }
    
    /**
     * Convert a string to camelCase for code generation
     * 
     * @param string $string
     * @return string
     */
    protected function camelCase($string)
    {
        // Remove non-alphanumeric characters and replace with spaces
        $string = preg_replace('/[^a-zA-Z0-9]/', ' ', $string);
        
        // Convert to camelCase
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        
        return $string;
    }
    
    /**
     * Send a prompt to the AI API
     * 
     * @param string $prompt
     * @return string AI response
     */
    protected function sendToAI($prompt)
    {
        // This is a simulated response for demonstration purposes
        // In a real implementation, this would make an API call to an AI service
        
        Log::info("Sending prompt to AI API: " . substr($prompt, 0, 100) . "...");
        
        // Simulate API call delay
        sleep(1);
        
        return "AI has analyzed the task and provided this response. This is a simulated response for demonstration purposes.";
    }
} 