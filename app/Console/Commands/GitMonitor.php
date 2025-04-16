<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class GitMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:monitor {--force : Force refresh the monitoring data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor git repository and generate reports';

    /**
     * Cache TTL in seconds (1 hour by default)
     *
     * @var int
     */
    protected $cacheTtl = 3600;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $forceRefresh = $this->option('force');
        $cacheKey = 'git_status_data';
        
        // Use cached data unless forced refresh
        if (!$forceRefresh && Cache::has($cacheKey)) {
            $this->info('Using cached monitoring data');
            $output = Cache::get($cacheKey);
            $this->writeReport($output);
            return 0;
        }
        
        $this->info('Gathering Git repository data...');
        $output = [];
        
        // Current branch
        $process = Process::fromShellCommandline('git branch --show-current');
        $process->run();
        $output['current_branch'] = trim($process->getOutput());
        
        // Recent commits with chunking for performance
        $process = Process::fromShellCommandline('git log --pretty=format:"%h|%an|%ad|%s" -n 20 --date=short');
        $process->run();
        $commits = [];
        foreach(explode("\n", $process->getOutput()) as $line) {
            if (empty($line)) continue;
            
            $parts = explode('|', $line, 4);
            if (count($parts) === 4) {
                [$hash, $author, $date, $message] = $parts;
                $commits[] = compact('hash', 'author', 'date', 'message');
            }
        }
        $output['recent_commits'] = $commits;
        
        // Active branches
        $process = Process::fromShellCommandline('git branch -a');
        $process->run();
        $branches = [];
        foreach(explode("\n", $process->getOutput()) as $branch) {
            if (trim($branch)) {
                $branches[] = trim(str_replace('*', '', $branch));
            }
        }
        $output['branches'] = $branches;
        
        // Get versions
        $process = Process::fromShellCommandline('git tag');
        $process->run();
        $output['versions'] = array_filter(explode("\n", $process->getOutput()));
        
        // Get roadmap data if available
        $output['roadmap'] = $this->getRoadmapData();
        
        // Get feature data if available
        $output['features'] = $this->getFeatureData();
        
        // Add project status overview
        $output['status'] = $this->generateStatusSummary($output);
        
        // Cache the result for performance
        Cache::put($cacheKey, $output, $this->cacheTtl);
        
        // Write report
        $this->writeReport($output);
        
        return 0;
    }
    
    /**
     * Get roadmap data from JSON file
     *
     * @return array
     */
    protected function getRoadmapData()
    {
        $path = base_path('roadmap.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            return $data ?: [];
        }
        
        return [];
    }
    
    /**
     * Get feature data from JSON file
     *
     * @return array
     */
    protected function getFeatureData()
    {
        $path = base_path('features.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            return $data ?: [];
        }
        
        return [];
    }
    
    /**
     * Generate summary of project status
     *
     * @param array $data
     * @return array
     */
    protected function generateStatusSummary($data)
    {
        $status = [
            'current_phase' => null,
            'active_features' => 0,
            'completed_features' => 0,
            'last_commit' => isset($data['recent_commits'][0]) ? $data['recent_commits'][0] : null,
            'latest_version' => !empty($data['versions']) ? end($data['versions']) : null,
            'branch_count' => count($data['branches']),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
        
        // Extract current phase
        if (!empty($data['roadmap']['current_phase'])) {
            $status['current_phase'] = $data['roadmap']['current_phase'];
        }
        
        // Count features
        if (!empty($data['features']['features'])) {
            foreach ($data['features']['features'] as $feature) {
                if (isset($feature['status']) && $feature['status'] === 'completed') {
                    $status['completed_features']++;
                } else {
                    $status['active_features']++;
                }
            }
        }
        
        return $status;
    }
    
    /**
     * Write monitoring report to file
     *
     * @param array $data
     * @return void
     */
    protected function writeReport($data)
    {
        // Create report directory if it doesn't exist
        if (!Storage::disk('local')->exists('reports')) {
            Storage::disk('local')->makeDirectory('reports');
        }
        
        $fileName = 'git_status_' . date('Y-m-d_His') . '.json';
        Storage::disk('local')->put('reports/' . $fileName, json_encode($data, JSON_PRETTY_PRINT));
        
        $this->info("Git monitoring report generated: storage/app/reports/{$fileName}");
        
        // Output key metrics
        $this->newLine();
        $this->info("Current Status Summary:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Current Branch', $data['current_branch']],
                ['Current Phase', $data['status']['current_phase'] ?? 'Not set'],
                ['Active Features', $data['status']['active_features']],
                ['Latest Version', $data['status']['latest_version'] ?? 'None'],
                ['Total Branches', $data['status']['branch_count']],
            ]
        );
    }
} 