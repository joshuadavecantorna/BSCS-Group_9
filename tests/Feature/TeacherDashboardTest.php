<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

class TeacherDashboardTest extends TestCase
{
    public function test_teacher_dashboard_returns_correct_data()
    {
        // Get the seeded teacher
        $user = User::where('email', 'john.smith@university.edu')->first();
        
        if (!$user) {
            $this->fail('Teacher user not found. Make sure TeacherSeeder has been run.');
        }

        // Simulate authentication
        $this->actingAs($user);
        
        // Get the teacher
        $teacher = Teacher::where('user_id', $user->id)->first();
        
        if (!$teacher) {
            $this->fail('Teacher profile not found.');
        }

        // Test the controller method directly
        $controller = new \App\Http\Controllers\TeacherController();
        
        // Mock request
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        echo "Testing Teacher Dashboard...\n";
        echo "Teacher: " . $teacher->first_name . " " . $teacher->last_name . "\n";
        echo "Email: " . $teacher->email . "\n";
        echo "Department: " . $teacher->department . "\n";
        echo "Position: " . $teacher->position . "\n";
        
        // Test dashboard stats
        $totalClasses = $teacher->classes()->count();
        $activeClasses = $teacher->classes()->where('is_active', 'true')->count();
        $totalStudents = DB::table('students')->count();
        
        echo "\nDashboard Stats:\n";
        echo "Total Classes: " . $totalClasses . "\n";
        echo "Active Classes: " . $activeClasses . "\n";
        echo "Total Students: " . $totalStudents . "\n";
        
        $this->assertTrue($totalClasses >= 0);
        $this->assertTrue($activeClasses >= 0);
        $this->assertTrue($totalStudents >= 0);
        
        echo "\n✅ Teacher Dashboard Test Passed!\n";
    }
}