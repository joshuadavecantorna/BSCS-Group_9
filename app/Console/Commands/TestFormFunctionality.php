<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Teacher;
use App\Models\AttendanceSession;
use Illuminate\Support\Facades\DB;

class TestFormFunctionality extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:forms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test form functionality for adding students, classes, and sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Form Functionality...');
        
        // Test 1: Test creating a new class
        $this->info('=== Testing Class Creation ===');
        try {
            $teacher = Teacher::first();
            if (!$teacher) {
                $this->error('No teacher found! Please create a teacher first.');
                return 1;
            }
            
            // Use raw SQL with explicit boolean
            $className = 'Test Class - ' . now()->format('Y-m-d H:i:s');
            $classCode = 'TEST-' . now()->format('His');
            $classId = DB::select("
                INSERT INTO class_models (name, course, section, year, teacher_id, schedule_time, schedule_days, class_code, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, true, now(), now()) 
                RETURNING id
            ", [
                $className,
                'BSCS',
                'TEST', 
                '1st Year',
                $teacher->id,
                '10:00:00',
                json_encode(['Monday', 'Wednesday', 'Friday']),
                $classCode
            ])[0]->id;
            
            $class = ClassModel::find($classId);
            
            $this->line("✓ Class created: {$class->name} (ID: {$class->id})");
            
        } catch (\Exception $e) {
            $this->error("✗ Class creation failed: " . $e->getMessage());
        }
        
        // Test 2: Test creating a new student
        $this->info('=== Testing Student Creation ===');
        try {
            $studentId = '25-TEST-' . now()->format('His');
            // Use raw SQL with explicit boolean
            $studentName = 'Test Student - ' . now()->format('Y-m-d H:i:s');
            $studentEmail = 'test.student.' . now()->format('His') . '@test.com';
            $studentDbId = DB::select("
                INSERT INTO students (student_id, name, email, course, year, section, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, true, now(), now()) 
                RETURNING id
            ", [
                $studentId,
                $studentName,
                $studentEmail,
                'BSCS',
                '1st Year',
                'A'
            ])[0]->id;
            
            $student = Student::find($studentDbId);
            
            $this->line("✓ Student created: {$student->name} (ID: {$student->student_id})");
            
        } catch (\Exception $e) {
            $this->error("✗ Student creation failed: " . $e->getMessage());
        }
        
        // Test 3: Test enrolling student in class
        $this->info('=== Testing Student Enrollment ===');
        try {
            if (isset($class) && isset($student)) {
                DB::table('class_student')->insert([
                    'class_model_id' => $class->id,
                    'student_id' => $student->id,
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $this->line("✓ Student enrolled in class successfully");
            } else {
                $this->line("⚠ Skipping enrollment test (missing class or student)");
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Student enrollment failed: " . $e->getMessage());
        }
        
        // Test 4: Test creating attendance session
        $this->info('=== Testing Attendance Session Creation ===');
        try {
            if (isset($class) && isset($teacher)) {
                $session = AttendanceSession::create([
                    'class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'session_name' => 'Test Session - ' . now()->format('Y-m-d H:i:s'),
                    'session_date' => now()->toDateString(),
                    'start_time' => now()->format('H:i:s'),
                    'status' => 'active',
                    'qr_code' => 'test_' . uniqid()
                ]);
                
                $this->line("✓ Attendance session created: {$session->session_name} (ID: {$session->id})");
            } else {
                $this->line("⚠ Skipping session test (missing class or teacher)");
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Attendance session creation failed: " . $e->getMessage());
        }
        
        // Test 5: Verify data retrieval
        $this->info('=== Testing Data Retrieval ===');
        
        $classCount = ClassModel::count();
        $studentCount = Student::count();
        $sessionCount = AttendanceSession::count();
        $enrollmentCount = DB::table('class_student')->count();
        
        $this->line("Current System State:");
        $this->line("  - Classes: {$classCount}");
        $this->line("  - Students: {$studentCount}");
        $this->line("  - Sessions: {$sessionCount}");
        $this->line("  - Enrollments: {$enrollmentCount}");
        
        $this->info('Form functionality tests completed!');
        
        return 0;
    }
}