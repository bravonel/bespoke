<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\Access\OperationalAccess;
use App\Services\Tasks\TaskNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task, OperationalAccess $access, TaskNotifier $notifier): RedirectResponse
    {
        abort_unless($access->canViewTask($request->user(), $task), 403);

        $request->merge(['body' => trim((string) $request->input('body'))]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $notifier->commented($task->loadMissing(['assignments', 'project']), $request->user());

        return back()->with('status', 'Comentario agregado.');
    }
}
