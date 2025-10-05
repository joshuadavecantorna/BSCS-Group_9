<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Teacher;
use App\Models\ClassModel;
use Illuminate\Support\Facades\DB;

class TestStudentReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:student-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test student reports functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Student Reports...');
        
        $teacher = Teacher::first();
        if (!$teacher) {
            $this->error('No teacher found');
            return 1;
        }
        
        $this->line("Teacher: {$teacher->first_name} {$teacher->last_name}");
        
        // Test the fixed query
        $classes = DB::table('class_models')
                    ->where('teacher_id', $teacher->id)
                    ->get();
        
        $this->line("Classes found: {$classes->count()}");
        
        $totalStudents = 0;
        foreach ($classes as $class) {
            $studentCount = DB::table('class_student')
                             ->where('class_model_id', $class->id)
                             ->count();
            
            $this->line("  - {$class->name}: {$studentCount} students");
            $totalStudents += $studentCount;
        }
        
        $this->line("Total students across all classes: {$totalStudents}");
        
        // Test student data retrieval
        if ($classes->count() > 0) {
            $firstClass = $classes->first();
            $students = DB::table('class_student')
                         ->join('students', 'class_student.student_id', '=', 'students.id')
                         ->where('class_student.class_model_id', $firstClass->id)
                         ->select('students.name', 'students.student_id', 'students.email')
                         ->limit(3)
                         ->get();
            
            $this->line("Sample students from {$firstClass->name}:");
            foreach ($students as $student) {
                $this->line("  - {$student->name} ({$student->student_id})");
            }
        }
        
        $this->info('Student reports test completed!');
        
        return 0;
    }
}