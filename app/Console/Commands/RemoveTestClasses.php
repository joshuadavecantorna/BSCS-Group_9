<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ClassModel;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;

class RemoveTestClasses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'classes:remove-test {--first=3 : Number of test classes to remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove test classes and associated attendance data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = $this->option('first');
        
        $this->info("Removing first {$count} test classes...");
        
        // Get the first N classes (typically test classes)
        $testClasses = ClassModel::orderBy('id')->take($count)->get();
        
        if ($testClasses->isEmpty()) {
            $this->info('No test classes found to remove.');
            return;
        }
        
        $this->info('Found ' . $testClasses->count() . ' classes to remove:');
        foreach ($testClasses as $class) {
            $this->line("- {$class->name} (ID: {$class->id})");
        }
        
        if (!$this->confirm('Do you want to proceed with deletion?')) {
            $this->info('Operation cancelled.');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            foreach ($testClasses as $class) {
                $this->info("Removing class: {$class->name}");
                
                // Get attendance sessions for this class
                $sessions = AttendanceSession::where('class_id', $class->id)->get();
                
                foreach ($sessions as $session) {
                    // Delete attendance records first
                    $recordsCount = AttendanceRecord::where('attendance_session_id', $session->id)->count();
                    AttendanceRecord::where('attendance_session_id', $session->id)->delete();
                    $this->line("  - Deleted {$recordsCount} attendance records");
                    
                    // Delete the session
                    $session->delete();
                    $this->line("  - Deleted attendance session: {$session->session_name}");
                }
                
                // Remove class-student relationships
                $enrollmentsCount = DB::table('class_student')->where('class_model_id', $class->id)->count();
                DB::table('class_student')->where('class_model_id', $class->id)->delete();
                $this->line("  - Deleted {$enrollmentsCount} student enrollments");
                
                // Finally delete the class
                $class->delete();
                $this->line("  - Deleted class: {$class->name}");
            }
            
            DB::commit();
            $this->info('Successfully removed all test classes and associated data!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error removing test classes: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}