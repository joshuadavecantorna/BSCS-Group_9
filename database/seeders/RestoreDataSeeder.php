<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassModel;

class RestoreDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User (if not exists)
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@attendify.com'],
            [
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role' => 'admin'
            ]
        );

        // Create Teacher Users
        $teacherData = [
            [
                'name' => 'John Dela Cruz',
                'email' => 'john.delacruz@attendify.com',
                'teacher_id' => 'TEACH-001',
                'department' => 'Computer Science',
                'position' => 'Professor'
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria.santos@attendify.com',
                'teacher_id' => 'TEACH-002',
                'department' => 'Computer Science',
                'position' => 'Assistant Professor'
            ]
        ];

        $teachers = [];
        foreach ($teacherData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password123'),
                    'role' => 'teacher'
                ]
            );

            $teacher = Teacher::firstOrCreate(
                ['teacher_id' => $data['teacher_id']],
                [
                    'user_id' => $user->id,
                    'first_name' => explode(' ', $data['name'])[0],
                    'last_name' => explode(' ', $data['name'])[2] ?? explode(' ', $data['name'])[1],
                    'middle_name' => count(explode(' ', $data['name'])) > 2 ? explode(' ', $data['name'])[1] : null,
                    'email' => $data['email'],
                    'department' => $data['department'],
                    'position' => $data['position'],
                    'is_active' => true
                ]
            );

            $teachers[] = $teacher;
        }

        // Create Student Users
        $studentData = [
            ['name' => 'Juan Santos', 'student_id' => '2021-00001', 'course' => 'BSCS', 'year' => 3, 'section' => 'A'],
            ['name' => 'Anna Cruz', 'student_id' => '2021-00002', 'course' => 'BSCS', 'year' => 3, 'section' => 'A'],
            ['name' => 'Carlos Reyes', 'student_id' => '2021-00003', 'course' => 'BSIT', 'year' => 2, 'section' => 'A']
        ];

        $students = [];
        foreach ($studentData as $data) {
            $email = strtolower(str_replace(' ', '.', $data['name'])) . '@student.attendify.com';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('password123'),
                    'role' => 'student'
                ]
            );

            $student = Student::firstOrCreate(
                ['student_id' => $data['student_id']],
                [
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $user->email,
                    'course' => $data['course'],
                    'year' => $data['year'],
                    'section' => $data['section'],
                    'is_active' => true
                ]
            );

            $students[] = $student;
        }

        // Create Classes
        $classes = [
            [
                'name' => 'Data Structures',
                'course' => 'BSCS',
                'section' => 'A',
                'year' => 3,
                'teacher_id' => $teachers[0]->user_id
            ],
            [
                'name' => 'Database Systems',
                'course' => 'BSCS',
                'section' => 'B',
                'year' => 3,
                'teacher_id' => $teachers[1]->user_id
            ]
        ];

        foreach ($classes as $classData) {
            ClassModel::firstOrCreate(
                [
                    'name' => $classData['name'],
                    'course' => $classData['course'],
                    'section' => $classData['section']
                ],
                [
                    'year' => $classData['year'],
                    'teacher_id' => $classData['teacher_id'],
                    'class_code' => strtoupper($classData['course'] . '-' . $classData['section'] . '-' . rand(100, 999))
                ]
            );
        }

        $this->command->info('Sample data restored successfully!');
        $this->command->info('Admin Login: admin@attendify.com / password123');
        $this->command->info('Teacher Login: john.delacruz@attendify.com / password123');
        $this->command->info('Student Login: juan.santos@student.attendify.com / password123');
    }
}