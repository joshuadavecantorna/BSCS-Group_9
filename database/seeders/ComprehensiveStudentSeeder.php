<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\User;

class ComprehensiveStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('class_student')->delete();
        
        // Create students from CSV data if they don't exist
        $csvFile = base_path('list_enrollled.csv');
        if (file_exists($csvFile)) {
            $this->seedFromCSV($csvFile);
        } else {
            $this->createSampleStudents();
        }
        
        // Enroll students in classes (3 students per class)
        $this->enrollStudentsInClasses();
    }
    
    private function seedFromCSV($csvFile)
    {
        $this->command->info('Seeding students from CSV...');
        
        $file = fopen($csvFile, 'r');
        $header = fgetcsv($file); // Skip header
        $count = 0;
        
        while (($data = fgetcsv($file)) !== false && $count < 21) { // Limit to 21 for 3 classes
            if (count($data) >= 7) {
                $course = $data[0];
                $year = $data[1];
                $studentId = $data[2];
                $lastName = $data[3];
                $firstName = $data[4];
                $middleName = $data[5];
                $gender = trim($data[6]);
                $email = $data[7] ?? '';
                
                $fullName = trim("$firstName $middleName $lastName");
                $cleanEmail = !empty($email) ? $email : strtolower(str_replace(' ', '.', $fullName)) . '@usm.edu.ph';
                
                // Create user account
                $user = User::firstOrCreate([
                    'email' => $cleanEmail
                ], [
                    'name' => $fullName,
                    'password' => bcrypt('password'),
                    'role' => 'student',
                    'student_id' => $studentId
                ]);
                
                // Create student record
                Student::firstOrCreate([
                    'student_id' => $studentId
                ], [
                    'user_id' => $user->id,
                    'name' => $fullName,
                    'email' => $cleanEmail,
                    'course' => $course,
                    'year' => $year,
                    'section' => 'A', // Default section
                    'is_active' => DB::raw('true')
                ]);
                
                $count++;
            }
        }
        
        fclose($file);
        $this->command->info("Created $count students from CSV");
    }
    
    private function createSampleStudents()
    {
        $this->command->info('Creating sample students...');
        
        for ($i = 1; $i <= 21; $i++) {
            $studentId = '25-' . str_pad($i, 5, '0', STR_PAD_LEFT);
            $name = "Student $i";
            $email = "student$i@usm.edu.ph";
            
            // Create user account
            $user = User::firstOrCreate([
                'email' => $email
            ], [
                'name' => $name,
                'password' => bcrypt('password'),
                'role' => 'student',
                'student_id' => $studentId
            ]);
            
            // Create student record
            Student::firstOrCreate([
                'student_id' => $studentId
            ], [
                'user_id' => $user->id,
                'name' => $name,
                'email' => $email,
                'course' => 'Bachelor of Science in Computer Science',
                'year' => '1st Year',
                'section' => 'A',
                'is_active' => DB::raw('true')
            ]);
        }
    }
    
    private function enrollStudentsInClasses()
    {
        $this->command->info('Enrolling students in classes...');
        
        $students = Student::take(21)->get(); // Get first 21 students
        $classes = ClassModel::all();
        
        if ($classes->count() < 3) {
            $this->command->error('Need at least 3 classes to distribute students');
            return;
        }
        
        // Take first 3 classes
        $classes = $classes->take(3);
        
        // Divide students into groups of 7 per class (21 students / 3 classes = 7 each)
        $studentsPerClass = 7;
        $studentChunks = $students->chunk($studentsPerClass);
        
        $classIndex = 0;
        foreach ($studentChunks as $chunk) {
            if ($classIndex >= $classes->count()) break;
            
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
            }
            
            $this->command->info("Enrolled {$chunk->count()} students in {$class->name}");
            $classIndex++;
        }
    }
}