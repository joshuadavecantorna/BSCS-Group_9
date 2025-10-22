<?php

namespace App\Http\Controllers;

use App\Models\ClassFile;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FilesController extends Controller
{
    /**
     * Display the files page, fetching paginated files from the database.
     */
    public function index(Request $request)
    {
        $files = ClassFile::query()
            ->with('teacher.user') // Eager load for performance
            ->when($request->input('search'), function ($query, $search) {
                $query->where('original_name', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Files', [ // Adjusted view path
            'files' => $files,
        ]);
    }

    /**
     * Store a newly uploaded file and its metadata in the database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,csv,xlsx,xls,doc,docx,jpg,jpeg,png,gif,mp4,mov,avi|max:20480', // 20MB max
            'category' => 'required|string|in:attendance-reports,student-records,class-schedules,parent-communications,administrative,templates',
            'class_linked' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $category = $request->input('category');
        $classLinked = $request->input('class_linked', 'General');
        $organizationPath = $this->createOrganizationPath($classLinked);
        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('files/' . $category . '/' . $organizationPath, $filename, 'public');
        $classModel = ClassModel::where('name', $classLinked)->first();

        ClassFile::create([
            'class_id' => $classModel ? $classModel->id : null,
            'teacher_id' => Auth::id(), // Assumes the logged-in user is a teacher
            'file_name' => $filename,
            'original_name' => $originalName,
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $request->input('description'),
            'visibility' => 'public',
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully!');
    }

    /**
     * Provide real-time metrics for the files subsystem.
     */
    public function metrics()
    {
        $totalFiles = ClassFile::count();
        $totalStorage = ClassFile::sum('file_size');
        $fileTypeBreakdown = ClassFile::query()
            ->selectRaw('file_type, count(*) as count')
            ->groupBy('file_type')
            ->get();
        $recentUploads = ClassFile::query()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_files' => $totalFiles,
            'total_storage_used' => $totalStorage,
            'total_storage_formatted' => $this->formatBytes($totalStorage),
            'file_type_breakdown' => $fileTypeBreakdown,
            'recent_uploads' => $recentUploads,
        ]);
    }

    /**
     * Download a file from storage.
     */
    public function download($filename)
    {
        $fileRecord = ClassFile::where('file_name', $filename)->firstOrFail();
        if (!Storage::disk('public')->exists($fileRecord->file_path)) {
            abort(404, 'File not found in storage.');
        }
        return response()->download(storage_path('app/public/' . $fileRecord->file_path), $fileRecord->original_name);
    }

    /**
     * Delete a file from storage and the database.
     */
    public function destroy($id)
    {
        $fileRecord = ClassFile::findOrFail($id);
        Storage::disk('public')->delete($fileRecord->file_path);
        $fileRecord->delete();

        return redirect()->back()->with('success', 'File deleted successfully.');
    }

    // ... (keep all private helper methods from the original file)
    private function createOrganizationPath($classLinked)
    {
        if ($classLinked === 'General' || empty($classLinked)) {
            return 'General';
        }
        if (preg_match('/^(BS[A-Z]+)-([A-Z])$/', $classLinked, $matches)) {
            $program = $matches[1];
            $section = $matches[2];
            $year = $this->determineYearFromProgram($program);
            return "{$year}/{$program}/{$section}";
        }
        return str_replace('-', '/', $classLinked);
    }

    private function determineYearFromProgram($program)
    {
        $yearMapping = [
            'BSCS' => '1', 'BSIT' => '1', 'BSCE' => '2', 'BSME' => '2',
        ];
        return $yearMapping[$program] ?? '1';
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}