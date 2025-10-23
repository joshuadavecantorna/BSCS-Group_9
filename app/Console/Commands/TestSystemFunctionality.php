<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\AttendanceSession;
use Illuminate\Support\Facades\DB;

class TestSystemFunctionality extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test system functionality for students, classes, and attendance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing System Functionality...');
        
        // Test 1: Student enrollment distribution
        $this->info('=== Testing Student Enrollment Distribution ===');
        $classes = ClassModel::all();
        
        foreach ($classes as $class) {
            $studentCount = DB::table('class_student')
                             ->where('class_model_id', $class->id)
                             ->count();
            
            $this->line("Class: {$class->name} - Students: {$studentCount}");
        }
        
        // Test 2: Verify students can be retrieved properly
        $this->info('=== Testing Student Data Retrieval ===');
        $totalStudents = Student::count();
        $activeStudents = Student::whereRaw('is_active = true')->count();
        
        $this->line("Total Students: {$totalStudents}");
        $this->line("Active Students: {$activeStudents}");
        
        // Test 3: Check class-student relationships
        $this->info('=== Testing Class-Student Relationships ===');
        $enrollments = DB::table('class_student')->count();
        $this->line("Total Enrollments: {$enrollments}");
        
        // Sample enrollment data
        $sampleEnrollments = DB::table('class_student')
                              ->join('students', 'class_student.student_id', '=', 'students.id')
                              ->join('class_models', 'class_student.class_model_id', '=', 'class_models.id')
                              ->select('students.name', 'class_models.name as class_name')
                              ->limit(5)
                              ->get();
        
        foreach ($sampleEnrollments as $enrollment) {
            $this->line("  - {$enrollment->name} enrolled in {$enrollment->class_name}");
        }
        
        // Test 4: Check attendance sessions
        $this->info('=== Testing Attendance Sessions ===');
        $sessions = AttendanceSession::count();
        $this->line("Total Attendance Sessions: {$sessions}");
        
        $this->info('System test completed!');
        
        return 0;
    }
}