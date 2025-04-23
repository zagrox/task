<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class AiSettingsController extends Controller
{
    /**
     * Show the AI settings page
     */
    public function index()
    {
        // Load the AI Assistant configuration
        $aiAssistantConfig = $this->getAiAssistantConfig();
        
        // Get AI task generation settings
        $aiTaskGenerationConfig = $this->getAiTaskGenerationConfig();
        
        // Get GitHub integration settings
        $githubConfig = $this->getGithubConfig();
        
        return view('ai.settings', [
            'aiAssistantConfig' => $aiAssistantConfig,
            'aiTaskGenerationConfig' => $aiTaskGenerationConfig,
            'githubConfig' => $githubConfig
        ]);
    }
    
    /**
     * Update AI Assistant configuration
     */
    public function updateAiAssistant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'version' => 'required|string|max:20',
            'responsibilities' => 'required|array'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $config = $this->getAiAssistantConfig();
        
        $config['assistant']['name'] = $request->input('name');
        $config['assistant']['version'] = $request->input('version');
        
        // Handle responsibilities
        if ($request->has('responsibilities')) {
            $config['assistant']['responsibilities'] = $request->input('responsibilities');
        }
        
        $config['assistant']['updated_at'] = date('Y-m-d');
        
        // Save the updated configuration
        File::put(base_path('ai-assistant.json'), json_encode($config, JSON_PRETTY_PRINT));
        
        return redirect()->route('ai.settings')->with('success', 'AI Assistant configuration updated successfully');
    }
    
    /**
     * Update AI Task Generation settings
     */
    public function updateAiTaskGeneration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'required|integer|min:1|max:30',
            'min_changes' => 'required|integer|min:1|max:100',
            'auto_schedule' => 'required|boolean',
            'schedule_frequency' => 'required|string|in:daily,weekly,monthly'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // Save settings in .env or database
        // Here we would update .env variables or database settings
        
        return redirect()->route('ai.settings')->with('success', 'AI Task Generation settings updated successfully');
    }
    
    /**
     * Update GitHub Integration settings
     */
    public function updateGithubIntegration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'repository' => 'required|string|max:100',
            'access_token' => 'nullable|string|max:255',
            'auto_sync' => 'required|boolean',
            'sync_direction' => 'required|in:both,to_github,from_github'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // Save settings in .env or database
        // Here we would update .env variables or database settings
        
        return redirect()->route('ai.settings')->with('success', 'GitHub Integration settings updated successfully');
    }
    
    /**
     * Get AI Assistant configuration
     */
    protected function getAiAssistantConfig()
    {
        $configFile = base_path('ai-assistant.json');
        
        if (!File::exists($configFile)) {
            // Return default configuration if file doesn't exist
            return [
                'assistant' => [
                    'name' => 'Task Dev Assistant',
                    'version' => '1.0.0',
                    'responsibilities' => [],
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d')
                ]
            ];
        }
        
        return json_decode(File::get($configFile), true);
    }
    
    /**
     * Get AI Task Generation configuration
     */
    protected function getAiTaskGenerationConfig()
    {
        return [
            'days' => env('AI_TASK_DAYS', 7),
            'min_changes' => env('AI_TASK_MIN_CHANGES', 5),
            'auto_schedule' => env('AI_TASK_AUTO_SCHEDULE', false),
            'schedule_frequency' => env('AI_TASK_SCHEDULE_FREQUENCY', 'daily')
        ];
    }
    
    /**
     * Get GitHub Integration configuration
     */
    protected function getGithubConfig()
    {
        return [
            'repository' => env('GITHUB_REPOSITORY', ''),
            'access_token' => env('GITHUB_ACCESS_TOKEN', ''),
            'auto_sync' => env('GITHUB_AUTO_SYNC', false),
            'sync_direction' => env('GITHUB_SYNC_DIRECTION', 'both')
        ];
    }
} 