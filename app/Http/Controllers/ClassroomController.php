<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassroomStoreRequest;
use App\Http\Requests\ClassroomUpdateRequest;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        $classrooms = Classroom::query()
            ->with('teacher:id,name,email')
            ->withCount('students')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('classes/Index', [
            'classrooms' => $classrooms,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('classes/Create');
    }

    public function store(ClassroomStoreRequest $request): RedirectResponse
    {
        Classroom::create($request->validated());
        return to_route('classes.index');
    }

    public function show(Classroom $classroom): Response
    {
        $classroom->load(['teacher:id,name,email', 'students:id,classroom_id,student_identifier,name,status']);
        return Inertia::render('classes/Show', [
            'classroom' => $classroom,
        ]);
    }

    public function edit(Classroom $classroom): Response
    {
        $classroom->load('teacher:id,name,email');
        return Inertia::render('classes/Edit', [
            'classroom' => $classroom,
        ]);
    }

    public function update(ClassroomUpdateRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());
        return to_route('classes.index');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        $classroom->delete();
        return back();
    }
}


