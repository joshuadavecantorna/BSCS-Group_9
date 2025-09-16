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
        // Get files from storage (in a real app, you'd store file metadata in database)
        $files = $this->getFilesFromStorage();
        
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
            'file' => 'required|file|max:10240', // 10MB max
            'category' => 'required|string|in:attendance-reports,student-records,class-schedules,parent-communications,administrative,templates'
        ]);

        $file = $request->file('file');
        $category = $request->input('category');
        
        // Generate unique filename
        $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        
        // Store file in category-specific directory
        $path = $file->storeAs('files/' . $category, $filename, 'public');

        // In a real app, you'd save file metadata to database
        // Return updated files list for instant loading

        return Inertia::render('Files', [
            'files' => $this->getFilesFromStorage()
        ]);
    }

    /**
     * Download a file.
     */
    public function download($filename)
    {
        // Find file in storage
        $filePath = $this->findFileInStorage($filename);
        
        if (!$filePath) {
            abort(404, 'File not found');
        }
        
        return Storage::disk('public')->download($filePath);
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
                    'type' => Storage::disk('public')->mimeType($filePath),
                    'uploaded_at' => date('Y-m-d\TH:i:s\Z', Storage::disk('public')->lastModified($filePath)),
                    'uploaded_by' => 'Teacher', // In real app, get from user session
                    'category' => $category,
                    'path' => $filePath
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
}
