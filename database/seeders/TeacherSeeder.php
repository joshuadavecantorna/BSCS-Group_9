<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
                // Create a user for the teacher (or find existing one)
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'john.smith@university.edu'],
            [
                'name' => 'Prof. John Smith',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

                // Create teacher profile (or find existing one)
        $teacher = \App\Models\Teacher::firstOrCreate(
            ['employee_id' => 'EMP001'],
            [
                'user_id' => $user->id,
                'first_name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@university.edu',
                'phone' => '+1234567890',
                'department' => 'Computer Science',
                'position' => 'Professor',
            ]
        );

        // Create sample classes using the 'classes' table (not class_models)
        DB::table('classes')->insert([
            'name' => 'Introduction to Programming',
            'course' => 'BSCS',
            'section' => 'A',
            'year' => '1st Year',
            'teacher_id' => $teacher->id,
            'schedule_time' => '08:00:00',
            'schedule_days' => json_encode(['Monday', 'Wednesday', 'Friday']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('classes')->insert([
            'name' => 'Data Structures and Algorithms',
            'course' => 'BSCS',
            'section' => 'B',
            'year' => '2nd Year',
            'teacher_id' => $teacher->id,
            'schedule_time' => '10:00:00',
            'schedule_days' => json_encode(['Tuesday', 'Thursday']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create sample students
        \App\Models\Student::create([
            'student_id' => '2024001',
            'name' => 'Alice Johnson',
            'email' => 'alice.johnson@student.edu',
            'year' => '1st Year',
            'course' => 'BSCS',
            'section' => 'A',
        ]);

        \App\Models\Student::create([
            'student_id' => '2024002',
            'name' => 'Bob Williams',
            'email' => 'bob.williams@student.edu',
            'year' => '2nd Year',
            'course' => 'BSCS',
            'section' => 'B',
        ]);
    }
}
