<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\ClassRoom;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function index()
    {
        $streams = Stream::with('classRoom')->orderBy('name')->get();

        return view('streams.index', compact('streams'));
    }

    public function create()
    {
        $classes = ClassRoom::orderBy('name')->get();

        return view('streams.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        Stream::create($validated);

        return redirect()
            ->route('streams.index')
            ->with('success', 'Stream created successfully.');
    }

    public function edit(Stream $stream)
    {
        $classes = ClassRoom::orderBy('name')->get();

        return view('streams.edit', compact('stream', 'classes'));
    }

    public function update(Request $request, Stream $stream)
    {
        $validated = $request->validate([
            'class_id' => ['required', 'exists:classes,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:30'],
        ]);

        $stream->update($validated);

        return redirect()
            ->route('streams.index')
            ->with('success', 'Stream updated successfully.');
    }

    public function destroy(Stream $stream)
    {
        $stream->delete();

        return redirect()
            ->route('streams.index')
            ->with('success', 'Stream deleted successfully.');
    }
}