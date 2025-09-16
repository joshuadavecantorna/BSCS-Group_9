<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentStoreRequest;
use App\Http\Requests\StudentUpdateRequest;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;

class StudentController extends Controller
{
    public function store(StudentStoreRequest $request, Classroom $class): RedirectResponse
    {
        $class->students()->create($request->validated());
        return back();
    }

    public function update(StudentUpdateRequest $request, Classroom $class, Student $student): RedirectResponse
    {
        $student->update($request->validated());
        return back();
    }

    public function destroy(Classroom $class, Student $student): RedirectResponse
    {
        $student->delete();
        return back();
    }
}



