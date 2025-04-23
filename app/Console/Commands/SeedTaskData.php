<?php

namespace App\Console\Commands;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedTaskData extends Command
{
    protected $signature = 'tasks:seed {count=50 : Number of tasks to seed}';
    protected $description = 'Seed the database with sample task data for reports';

    private $features = [
        'Authentication', 'Dashboard', 'Task Management', 'User Management', 
        'Reporting', 'API Integration', 'Documentation', 'ZagroxAI'
    ];

    private $phases = [
        'Planning', 'Design', 'Development', 'Testing', 'Deployment', 'Maintenance'
    ];

    private $versions = [
        '1.0.0', '1.0.1', '1.1.0', '1.1.1', '1.2.0', '2.0.0'
    ];

    private $statuses = [
        'pending', 'in-progress', 'completed', 'blocked', 'review'
    ];

    private $priorities = [
        'low', 'medium', 'high', 'critical'
    ];

    private $titles = [
        'Fix bug in %s module',
        'Implement new feature for %s',
        'Update documentation for %s',
        'Refactor %s code',
        'Optimize %s performance',
        'Add unit tests for %s',
        'Create UI design for %s',
        'Integrate %s with third-party service',
        'Review %s functionality',
        'Deploy %s to production'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $count = (int) $this->argument('count');
        
        if ($this->confirm("Are you sure you want to seed {$count} tasks? This will remove existing tasks.")) {
            // Clear existing tasks
            DB::table('tasks')->truncate();
            
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            
            for ($i = 0; $i < $count; $i++) {
                $this->createTask();
                $bar->advance();
            }
            
            $bar->finish();
            $this->info("\n{$count} tasks have been created successfully!");
        }
        
        return Command::SUCCESS;
    }

    private function createTask()
    {
        $feature = $this->features[array_rand($this->features)];
        $title = sprintf($this->titles[array_rand($this->titles)], $feature);
        
        // Determine if task is assigned to AI (20% chance)
        $assignedTo = rand(1, 5) === 1 ? 'ai' : 'user';
        
        // Create due dates distribution
        $dueDateOptions = [
            'past' => 20,     // 20% chance of overdue
            'today' => 15,    // 15% chance of due today
            'week' => 35,     // 35% chance of due this week
            'future' => 30,   // 30% chance of due in the future
        ];
        
        $dueDate = $this->generateDueDate($dueDateOptions);
        
        // Status probabilities
        $statusProbabilities = [
            'pending' => 30,
            'in-progress' => 25,
            'completed' => 35,
            'blocked' => 5,
            'review' => 5
        ];
        
        $status = $this->getRandomWithProbability($statusProbabilities);
        
        // If due date is in the past, make it more likely to be completed
        if ($dueDate < Carbon::today() && rand(1, 100) <= 70) {
            $status = 'completed';
        }
        
        // Priority probabilities
        $priorityProbabilities = [
            'low' => 25,
            'medium' => 45,
            'high' => 20,
            'critical' => 10
        ];
        
        $priority = $this->getRandomWithProbability($priorityProbabilities);
        
        // Create the task
        Task::create([
            'title' => $title,
            'description' => "This is a sample task for the {$feature} feature in the {$this->phases[array_rand($this->phases)]} phase.",
            'status' => $status,
            'priority' => $priority,
            'due_date' => $dueDate->format('Y-m-d'),
            'estimated_hours' => rand(1, 40),
            'actual_hours' => rand(0, 60),
            'assigned_to' => $assignedTo,
            'created_by' => 'system',
            'feature' => $feature,
            'phase' => $this->phases[array_rand($this->phases)],
            'version' => $this->versions[array_rand($this->versions)],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    private function generateDueDate($options)
    {
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach ($options as $type => $probability) {
            $cumulative += $probability;
            
            if ($rand <= $cumulative) {
                switch ($type) {
                    case 'past':
                        return Carbon::now()->subDays(rand(1, 14));
                    case 'today':
                        return Carbon::today();
                    case 'week':
                        return Carbon::today()->addDays(rand(1, 7));
                    case 'future':
                        return Carbon::today()->addDays(rand(8, 30));
                }
            }
        }
        
        return Carbon::today()->addDays(rand(1, 7));
    }
    
    private function getRandomWithProbability($options)
    {
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach ($options as $value => $probability) {
            $cumulative += $probability;
            
            if ($rand <= $cumulative) {
                return $value;
            }
        }
        
        // Default to the first option if something goes wrong
        return array_key_first($options);
    }
} 