<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FilesController extends Controller
{
    /**
     * Display the files page.
     */
    public function index()
    {
        // Sample data for demonstration
        $files = [
            [
                'id' => '1',
                'name' => 'Attendance_Report_BSCS_A_January.pdf',
                'size' => 245760, // 240KB
                'type' => 'application/pdf',
                'uploaded_at' => now()->subDays(2)->toISOString(),
                'uploaded_by' => 'John Doe',
                'category' => 'attendance-reports',
                'class_linked' => 'BSCS-A',
                'path' => 'files/attendance-reports/1/BSCS/A/Attendance_Report_BSCS_A_January.pdf'
            ],
            [
                'id' => '2',
                'name' => 'Student_Grades_BSIT_B.xlsx',
                'size' => 512000, // 500KB
                'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploaded_at' => now()->subDays(5)->toISOString(),
                'uploaded_by' => 'Jane Smith',
                'category' => 'student-records',
                'class_linked' => 'BSIT-B',
                'path' => 'files/student-records/1/BSIT/B/Student_Grades_BSIT_B.xlsx'
            ],
            [
                'id' => '3',
                'name' => 'Class_Schedule_Spring_2024.csv',
                'size' => 15360, // 15KB
                'type' => 'text/csv',
                'uploaded_at' => now()->subWeek()->toISOString(),
                'uploaded_by' => 'Admin User',
                'category' => 'class-schedules',
                'class_linked' => 'General',
                'path' => 'files/class-schedules/General/Class_Schedule_Spring_2024.csv'
            ],
            [
                'id' => '4',
                'name' => 'Parent_Meeting_Notes_BSCE_A.pdf',
                'size' => 180000, // 176KB
                'type' => 'application/pdf',
                'uploaded_at' => now()->subDays(3)->toISOString(),
                'uploaded_by' => 'Sarah Johnson',
                'category' => 'parent-communications',
                'class_linked' => 'BSCE-A',
                'path' => 'files/parent-communications/2/BSCE/A/Parent_Meeting_Notes_BSCE_A.pdf'
            ]
        ];

        return Inertia::render('Files', [
            'files' => $files
        ]);
    }

    /**
     * Store a newly uploaded file.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,csv,xlsx,xls,doc,docx|max:10240', // 10MB max, only specified file types
            'category' => 'required|string|in:attendance-reports,student-records,class-schedules,parent-communications,administrative,templates',
            'class_linked' => 'nullable|string' // Optional class association
        ]);

        $file = $request->file('file');
        $category = $request->input('category');
        $classLinked = $request->input('class_linked', 'General');
        
        // Auto-organization: Create folder structure like "1/BSCS/A" or similar
        $organizationPath = $this->createOrganizationPath($classLinked);
        
        // Generate unique filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;
        
        // Store file in organized directory structure
        $fullPath = 'files/' . $category . '/' . $organizationPath;
        $path = $file->storeAs($fullPath, $filename, 'public');

        // In a real app, you'd save file metadata to database with class linking
        
        return redirect()->back()->with('success', 'File uploaded successfully!');
    }

    /**
     * Download a file.
     */
    public function download($filename)
    {
        // Find file in storage
        $filePath = $this->findFileInStorage($filename);
        
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            abort(404, 'File not found');
        }
        
        return response()->download(storage_path('app/public/' . $filePath));
    }

    /**
     * Delete a file.
     */
    public function destroy($filename)
    {
        // Find and delete file
        $filePath = $this->findFileInStorage($filename);
        
        if (!$filePath) {
            abort(404, 'File not found');
        }
        
        Storage::disk('public')->delete($filePath);

        return Inertia::render('Files', [
            'files' => $this->getFilesFromStorage()
        ]);
    }

    /**
     * Share a file with specified emails.
     */
    public function share(Request $request, $filename)
    {
        $request->validate([
            'emails' => 'required|string'
        ]);

        // Find file
        $filePath = $this->findFileInStorage($filename);
        
        if (!$filePath) {
            abort(404, 'File not found');
        }

        $emails = array_map('trim', explode(',', $request->input('emails')));
        
        // In a real app, you would:
        // 1. Validate email addresses
        // 2. Send sharing invitations via email
        // 3. Store sharing permissions in database
        // 4. Generate secure sharing links
        
        return response()->json([
            'success' => true,
            'message' => 'File shared successfully',
            'shared_with' => $emails
        ]);
    }

    /**
     * Get files from storage (helper method).
     */
    private function getFilesFromStorage()
    {
        $files = [];
        $directories = ['attendance-reports', 'student-records', 'class-schedules', 'parent-communications', 'administrative', 'templates'];
        
        foreach ($directories as $category) {
            $categoryFiles = Storage::disk('public')->files('files/' . $category);
            
            foreach ($categoryFiles as $filePath) {
                $files[] = [
                    'id' => md5($filePath),
                    'name' => basename($filePath),
                    'size' => Storage::disk('public')->size($filePath),
                    'type' => $this->getMimeTypeFromPath($filePath),
                    'uploaded_at' => date('Y-m-d\TH:i:s\Z', Storage::disk('public')->lastModified($filePath)),
                    'uploaded_by' => 'Teacher', // In real app, get from user session
                    'category' => $category,
                    'path' => $filePath,
                    'class_linked' => $this->extractClassFromPath($filePath)
                ];
            }
        }
        
        // Sort by upload date (newest first)
        usort($files, function($a, $b) {
            return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
        });
        
        return $files;
    }

    /**
     * Find file in storage by filename (helper method).
     */
    private function findFileInStorage($filename)
    {
        $directories = ['attendance-reports', 'student-records', 'class-schedules', 'parent-communications', 'administrative', 'templates'];
        
        foreach ($directories as $category) {
            $files = Storage::disk('public')->files('files/' . $category);
            
            foreach ($files as $filePath) {
                if (basename($filePath) === $filename) {
                    return $filePath;
                }
            }
        }
        
        return null;
    }

    /**
     * Create organization path based on class name (e.g., "1/BSCS/A").
     */
    private function createOrganizationPath($classLinked)
    {
        // Extract components from class name (e.g., "BSCS-A" -> "1/BSCS/A")
        if ($classLinked === 'General' || empty($classLinked)) {
            return 'General';
        }

        // Parse class name patterns like "BSCS-A", "BSIT-B", etc.
        if (preg_match('/^(BS[A-Z]+)-([A-Z])$/', $classLinked, $matches)) {
            $program = $matches[1]; // e.g., "BSCS"
            $section = $matches[2]; // e.g., "A"
            
            // Determine year based on program (simplified logic)
            $year = $this->determineYearFromProgram($program);
            
            return "{$year}/{$program}/{$section}";
        }

        // Fallback to just the class name
        return str_replace('-', '/', $classLinked);
    }

    /**
     * Extract class information from file path.
     */
    private function extractClassFromPath($filePath)
    {
        $pathParts = explode('/', $filePath);
        
        // Look for class organization pattern in path
        if (count($pathParts) >= 4) {
            // Extract from pattern like "files/category/1/BSCS/A/filename.pdf"
            $year = $pathParts[2] ?? '';
            $program = $pathParts[3] ?? '';
            $section = $pathParts[4] ?? '';
            
            if ($year && $program && $section && $year !== 'General') {
                return "{$program}-{$section}";
            }
        }
        
        return 'General';
    }

    /**
     * Determine academic year from program name.
     */
    private function determineYearFromProgram($program)
    {
        // This is simplified logic - in a real app, you'd have proper academic year management
        $yearMapping = [
            'BSCS' => '1', // 1st year
            'BSIT' => '1', // 1st year
            'BSCE' => '2', // 2nd year (example)
            'BSME' => '2', // 2nd year (example)
        ];

        return $yearMapping[$program] ?? '1';
    }

    /**
     * Get MIME type from file path based on extension.
     */
    private function getMimeTypeFromPath($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}
