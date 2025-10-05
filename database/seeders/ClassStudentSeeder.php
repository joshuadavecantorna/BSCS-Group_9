<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample classes
        $teachers = \App\Models\Teacher::all();
        $students = \App\Models\Student::all();
        
        if ($teachers->isEmpty()) {
            // Create a sample teacher if none exist
            $user = \App\Models\User::create([
                'name' => 'Sample Teacher',
                'email' => 'teacher@example.com',
                'password' => bcrypt('password'),
                'role' => 'teacher'
            ]);
            
            $teacher = \App\Models\Teacher::create([
                'user_id' => $user->id,
                'employee_id' => 'TECH-001',
                'first_name' => 'Sample',
                'last_name' => 'Teacher',
                'email' => 'teacher@example.com',
                'department' => 'Computer Science',
                'position' => 'Professor',
                'is_active' => true
            ]);
        } else {
            $teacher = $teachers->first();
        }
        
        // Create sample classes
        $classes = [
            [
                'name' => 'Advanced Computer Science',
                'course' => 'BSCS',
                'section' => 'A',
                'year' => '4th',
                'subject' => 'Advanced Programming',
                'description' => 'Advanced topics in computer science and programming'
            ],
            [
                'name' => 'Database Management',
                'course' => 'BSIT',
                'section' => 'B', 
                'year' => '3rd',
                'subject' => 'Database Systems',
                'description' => 'Database design and management principles'
            ],
            [
                'name' => 'Web Development',
                'course' => 'BSCS',
                'section' => 'C',
                'year' => '2nd',
                'subject' => 'Web Technologies',
                'description' => 'Frontend and backend web development'
            ]
        ];
        
        foreach ($classes as $classData) {
            $class = \App\Models\ClassModel::create(array_merge($classData, [
                'teacher_id' => $teacher->id,
                'schedule_time' => '09:00:00',
                'schedule_days' => ['Monday', 'Wednesday', 'Friday'],
                'is_active' => true
            ]));
            
            // Enroll some students if they exist
            if ($students->isNotEmpty()) {
                $enrolledStudents = $students->random(min(5, $students->count()));
                foreach ($enrolledStudents as $student) {
                    DB::table('class_student')->insert([
                        'class_model_id' => $class->id,
                        'student_id' => $student->id,
                        'status' => 'enrolled',
                        'enrolled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
        
        // Create sample students if none exist
        if ($students->isEmpty()) {
            $sampleStudents = [
                ['student_id' => 'STU-001', 'name' => 'John Doe', 'year' => '1st', 'course' => 'BSCS', 'section' => 'A'],
                ['student_id' => 'STU-002', 'name' => 'Jane Smith', 'year' => '2nd', 'course' => 'BSIT', 'section' => 'B'],
                ['student_id' => 'STU-003', 'name' => 'Bob Johnson', 'year' => '3rd', 'course' => 'BSEE', 'section' => 'C'],
                ['student_id' => 'STU-004', 'name' => 'Alice Brown', 'year' => '1st', 'course' => 'BSCS', 'section' => 'A'],
                ['student_id' => 'STU-005', 'name' => 'Charlie Wilson', 'year' => '2nd', 'course' => 'BSIT', 'section' => 'B'],
            ];
            
            foreach ($sampleStudents as $studentData) {
                $student = \App\Models\Student::create(array_merge($studentData, [
                    'email' => strtolower(str_replace(' ', '.', $studentData['name'])) . '@student.example.com',
                    'is_active' => true
                ]));
                
                // Enroll in a random class
                $randomClass = \App\Models\ClassModel::inRandomOrder()->first();
                if ($randomClass) {
                    DB::table('class_student')->insertOrIgnore([
                        'class_model_id' => $randomClass->id,
                        'student_id' => $student->id,
                        'status' => 'enrolled',
                        'enrolled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }
}
