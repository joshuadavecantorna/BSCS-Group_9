<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample students if they don't exist
        $students = [];
        for ($i = 1; $i <= 10; $i++) {
            $students[] = Student::firstOrCreate([
                'student_id' => 'STU-2024-' . str_pad($i, 3, '0', STR_PAD_LEFT)
            ], [
                'name' => 'Student ' . $i . ' Test',
                'email' => 'student' . $i . '@test.com',
                'course' => $i <= 5 ? 'BSCS' : 'BSIT',
                'year' => rand(1, 4) . 'rd Year',
                'section' => 'A',
            ]);
        }

        // Get existing teachers
        $teachers = Teacher::where('is_active', 'true')->get();
        
        if ($teachers->count() > 0) {
            // Create some sample classes if they don't exist
            $classes = [];
            foreach ($teachers->take(3) as $index => $teacher) {
                $classes[] = ClassModel::firstOrCreate([
                    'name' => 'Advanced Programming ' . ($index + 1),
                    'teacher_id' => $teacher->user_id
                ], [
                    'course' => $index % 2 == 0 ? 'BSCS' : 'BSIT',
                    'section' => 'A',
                    'year' => '3rd Year',
                    'schedule_time' => '08:00:00',
                    'schedule_days' => json_encode(['monday', 'wednesday', 'friday']),
                ]);
            }

            // Create attendance sessions for the past 7 days
            $statuses = ['present', 'absent', 'excused', 'late'];
            
            foreach ($classes as $class) {
                for ($day = 7; $day >= 1; $day--) {
                    $sessionDate = Carbon::now()->subDays($day);
                    
                    // Find the teacher record for this class
                    $teacher = Teacher::where('user_id', $class->teacher_id)->first();
                    
                    $session = AttendanceSession::create([
                        'class_id' => $class->id,
                        'teacher_id' => $teacher->id,
                        'session_name' => 'Regular Class Session',
                        'session_date' => $sessionDate->format('Y-m-d'),
                        'start_time' => '08:00:00',
                        'end_time' => '09:00:00',
                        'status' => 'completed',
                        'notes' => 'Regular class session',
                    ]);

                    // Create attendance records for each student
                    foreach ($students as $student) {
                        // Randomly assign attendance status (80% present, 10% absent, 5% excused, 5% late)
                        $rand = rand(1, 100);
                        if ($rand <= 80) {
                            $status = 'present';
                            $markedAt = $sessionDate->copy()->setTime(8, rand(0, 15));
                        } elseif ($rand <= 90) {
                            $status = 'absent';
                            $markedAt = $sessionDate->copy()->setTime(8, 0); // Mark at session start for absent
                        } elseif ($rand <= 95) {
                            $status = 'excused';
                            $markedAt = $sessionDate->copy()->setTime(8, rand(0, 15));
                        } else {
                            $status = 'late';
                            $markedAt = $sessionDate->copy()->setTime(8, rand(16, 30));
                        }

                        AttendanceRecord::create([
                            'attendance_session_id' => $session->id,
                            'student_id' => $student->id,
                            'status' => $status,
                            'marked_at' => $markedAt,
                            'marked_by' => 'teacher',
                            'notes' => $status == 'excused' ? 'Medical excuse' : null,
                        ]);
                    }
                }
            }

            $this->command->info('Sample attendance data created successfully!');
        } else {
            $this->command->info('No active teachers found. Please create teachers first.');
        }
    }
}
