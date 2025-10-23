<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\ClassModel;

class StudentClassEnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all students and classes
        $students = Student::all();
        $classes = ClassModel::all();
        
        if ($students->isEmpty() || $classes->isEmpty()) {
            $this->command->info('No students or classes found. Please seed them first.');
            return;
        }
        
        // Clear existing enrollments
        DB::table('class_student')->delete();
        
        // Divide students into groups of 3 per class
        $studentsPerClass = 3;
        $studentChunks = $students->chunk($studentsPerClass);
        
        $classIndex = 0;
        foreach ($studentChunks as $chunk) {
            if ($classIndex >= $classes->count()) {
                // If we have more chunks than classes, cycle back to first class
                $classIndex = 0;
            }
            
            $class = $classes[$classIndex];
            
            foreach ($chunk as $student) {
                DB::table('class_student')->insert([
                    'class_model_id' => $class->id,
                    'student_id' => $student->id,
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $this->command->info("Enrolled {$student->name} in {$class->name}");
            }
            
            $classIndex++;
        }
        
        $this->command->info('Student enrollment completed!');
    }
}