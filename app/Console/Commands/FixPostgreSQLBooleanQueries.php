<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FixPostgreSQLBooleanQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:postgresql-booleans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix PostgreSQL boolean queries throughout the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing PostgreSQL boolean queries...');
        
        // Files to check and fix
        $files = [
            'app/Http/Controllers/TeacherController.php',
            'app/Http/Controllers/StudentController.php',
            'app/Http/Controllers/AdminController.php',
        ];
        
        $patterns = [
            // Common boolean query patterns that need fixing
            "->where('is_active', true)" => "->whereRaw('is_active = true')",
            "->where('is_active', false)" => "->whereRaw('is_active = false')",
            "->where('is_active', 1)" => "->whereRaw('is_active = true')",
            "->where('is_active', 0)" => "->whereRaw('is_active = false')",
            "whereRaw('is_active = true')" => "whereRaw('is_active = true')", // Already correct
        ];
        
        foreach ($files as $file) {
            if (File::exists(base_path($file))) {
                $content = File::get(base_path($file));
                $originalContent = $content;
                
                foreach ($patterns as $search => $replace) {
                    if ($search !== $replace) { // Don't replace if already correct
                        $content = str_replace($search, $replace, $content);
                    }
                }
                
                if ($content !== $originalContent) {
                    File::put(base_path($file), $content);
                    $this->line("Fixed boolean queries in: $file");
                } else {
                    $this->line("No boolean issues found in: $file");
                }
            }
        }
        
        $this->info('PostgreSQL boolean query fixes completed!');
        
        return 0;
    }
}