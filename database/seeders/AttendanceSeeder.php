<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\User;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users if they don't exist
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Prof. Juan Dela Cruz',
                'password' => bcrypt('password'),
                'role' => 'teacher',
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        // Create classes if they don't exist
        $classes = [
            [
                'name' => 'BSCS-A',
                'course' => 'Computer Science',
                'section' => 'A',
                'year' => '3rd Year',
                'schedule' => '8:00 AM - 10:00 AM',
                'schedule_time' => '08:00:00',
                'schedule_days' => ['monday', 'wednesday', 'friday']
            ],
            [
                'name' => 'BSIT-B',
                'course' => 'Information Technology',
                'section' => 'B', 
                'year' => '2nd Year',
                'schedule' => '10:00 AM - 12:00 PM',
                'schedule_time' => '10:00:00',
                'schedule_days' => ['tuesday', 'thursday']
            ],
            [
                'name' => 'BSCS-C',
                'course' => 'Computer Science',
                'section' => 'C',
                'year' => '1st Year',
                'schedule' => '1:00 PM - 3:00 PM',
                'schedule_time' => '13:00:00',
                'schedule_days' => ['monday', 'wednesday', 'friday']
            ],
            [
                'name' => 'BSIT-D',
                'course' => 'Information Technology',
                'section' => 'D',
                'year' => '4th Year',
                'schedule' => '3:00 PM - 5:00 PM',
                'schedule_time' => '15:00:00',
                'schedule_days' => ['tuesday', 'thursday']
            ],
        ];

        $createdClasses = [];
        foreach ($classes as $classData) {
            $class = ClassModel::firstOrCreate(
                ['name' => $classData['name']],
                [
                    'course' => $classData['course'],
                    'section' => $classData['section'],
                    'year' => $classData['year'],
                    'schedule' => $classData['schedule'],
                    'schedule_time' => $classData['schedule_time'],
                    'schedule_days' => json_encode($classData['schedule_days']),
                    'teacher_id' => $teacher->id,
                ]
            );
            $createdClasses[] = $class;
        }

        // Create student users and students
        $studentData = [
            ['name' => 'Ivan Dela Cruz', 'email' => 'ivan@example.com', 'course' => 'Computer Science', 'year' => '3rd Year', 'section' => 'A'],
            ['name' => 'Maria Santos', 'email' => 'maria@example.com', 'course' => 'Information Technology', 'year' => '2nd Year', 'section' => 'B'],
            ['name' => 'Pedro Garcia', 'email' => 'pedro@example.com', 'course' => 'Computer Science', 'year' => '1st Year', 'section' => 'C'],
            ['name' => 'Ana Rodriguez', 'email' => 'ana@example.com', 'course' => 'Information Technology', 'year' => '4th Year', 'section' => 'D'],
            ['name' => 'Jose Martinez', 'email' => 'jose@example.com', 'course' => 'Computer Science', 'year' => '3rd Year', 'section' => 'A'],
            ['name' => 'Carmen Lopez', 'email' => 'carmen@example.com', 'course' => 'Information Technology', 'year' => '2nd Year', 'section' => 'B'],
            ['name' => 'Miguel Torres', 'email' => 'miguel@example.com', 'course' => 'Computer Science', 'year' => '1st Year', 'section' => 'C'],
            ['name' => 'Sofia Reyes', 'email' => 'sofia@example.com', 'course' => 'Information Technology', 'year' => '4th Year', 'section' => 'D'],
            ['name' => 'Carlos Mendoza', 'email' => 'carlos@example.com', 'course' => 'Computer Science', 'year' => '3rd Year', 'section' => 'A'],
            ['name' => 'Lucia Fernandez', 'email' => 'lucia@example.com', 'course' => 'Information Technology', 'year' => '2nd Year', 'section' => 'B'],
        ];

        $students = [];
        foreach ($studentData as $index => $data) {
            // Create user for student
            $studentUser = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt('password'),
                    'role' => 'student',
                ]
            );

            // Create student record (matching actual table structure)
            $student = Student::firstOrCreate(
                ['student_id' => 'STU-2024-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'year' => $data['year'],
                    'course' => $data['course'],
                    'section' => $data['section'],
                ]
            );

            $students[] = $student;
        }

        // Create attendance records for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $statuses = ['present', 'absent', 'excused', 'late'];
        $statusWeights = [0.7, 0.15, 0.1, 0.05]; // 70% present, 15% absent, 10% excused, 5% late

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // Skip weekends
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($students as $student) {
                // Find the appropriate class for this student based on their course and section
                $studentClass = null;
                foreach ($createdClasses as $class) {
                    if ($class->course === $student->course && $class->section === $student->section) {
                        $studentClass = $class;
                        break;
                    }
                }

                if ($studentClass) {
                    // Random chance of attendance record existing (some days students might not have classes)
                    if (rand(1, 100) <= 85) { // 85% chance of having class
                        $status = $this->getRandomWeightedStatus($statuses, $statusWeights);
                        
                        Attendance::create([
                            'student_id' => $student->id,
                            'class_id' => $studentClass->id,
                            'teacher_id' => $teacher->id,
                            'date' => $date->format('Y-m-d'),
                            'status' => $status,
                            'scanned_at' => $date->copy()->setHour(8)->setMinute(rand(0, 30)),
                            'created_at' => $date->copy(),
                            'updated_at' => $date->copy(),
                        ]);
                    }
                }
            }
        }

        $this->command->info('Created attendance records for ' . count($students) . ' students over 30 days');
    }

    /**
     * Get a random status based on weights
     */
    private function getRandomWeightedStatus(array $statuses, array $weights): string
    {
        $random = mt_rand() / mt_getrandmax();
        $cumulativeWeight = 0;

        for ($i = 0; $i < count($statuses); $i++) {
            $cumulativeWeight += $weights[$i];
            if ($random <= $cumulativeWeight) {
                return $statuses[$i];
            }
        }

        return $statuses[0]; // Fallback
    }
}
