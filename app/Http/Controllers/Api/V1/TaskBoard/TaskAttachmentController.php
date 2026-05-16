<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\TaskBoard;

use App\Domain\TaskBoard\Events\AttachmentAdded;
use App\Domain\TaskBoard\Events\AttachmentRemoved;
use App\Domain\TaskBoard\Models\Task;
use App\Domain\TaskBoard\Models\TaskAttachment;
use App\Domain\TaskBoard\Services\TaskActivityService;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskBoard\TaskAttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentController extends Controller
{
    private const ALLOWED_MIMES = [
        'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint',
        'application/zip', 'application/x-zip-compressed',
        'text/plain', 'text/csv', 'text/markdown',
        'video/mp4', 'video/quicktime',
        'audio/mpeg', 'audio/wav',
    ];

    private const MAX_BYTES = 25 * 1024 * 1024; // 25 MB

    public function index(Request $request, Task $task): AnonymousResourceCollection
    {
        abort_unless($request->user()?->can('view_tasks'), 403);

        return TaskAttachmentResource::collection(
            $task->attachments()->with('uploader:id,name')->latest()->get(),
        );
    }

    public function store(Request $request, Task $task, TaskActivityService $activity): JsonResource
    {
        abort_unless($request->user()?->can('edit_tasks') || $request->user()?->can('comment_tasks'), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:'.(self::MAX_BYTES / 1024)],
            'comment_id' => ['nullable', 'integer', 'exists:task_comments,id'],
        ]);

        $file = $request->file('file');
        $mime = $file->getClientMimeType() ?: $file->getMimeType();

        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            return response()->json(['message' => 'File type not allowed.'], 422);
        }

        $disk = config('filesystems.default');
        $filename = Str::uuid()->toString().'.'.($file->getClientOriginalExtension() ?: 'bin');
        $folder = 'tenants/'.$task->tenant_id.'/task-attachments/'.$task->id;
        $path = $file->storeAs($folder, $filename, $disk);

        $checksum = hash_file('sha256', $file->getRealPath()) ?: null;

        /** @var TaskAttachment $attachment */
        $attachment = TaskAttachment::create([
            'tenant_id' => $task->tenant_id,
            'task_id' => $task->id,
            'comment_id' => $request->integer('comment_id') ?: null,
            'uploaded_by_id' => $request->user()->id,
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size_bytes' => $file->getSize(),
            'checksum' => $checksum,
        ]);

        $activity->attachment($task, (int) $request->user()->id, $attachment->id, $attachment->original_filename, 'added');

        AttachmentAdded::dispatch($attachment, (int) $task->board_id, (int) $request->user()->id);

        return TaskAttachmentResource::make($attachment->load('uploader'));
    }

    public function download(Request $request, TaskAttachment $attachment): StreamedResponse|BinaryFileResponse
    {
        // Signed URLs encode the validity; if Laravel routed us here the signature is fine.
        // Also enforce tenant scope as belt-and-braces.
        abort_unless($attachment->tenant_id === (int) app('tenant.id'), 404);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_filename);
    }

    public function destroy(Request $request, TaskAttachment $attachment, TaskActivityService $activity)
    {
        $isOwner = $request->user()?->id === $attachment->uploaded_by_id;
        abort_unless($isOwner || $request->user()?->can('delete_tasks'), 403);

        $task = $attachment->task;
        $filename = $attachment->original_filename;
        $attachmentId = (int) $attachment->id;
        $taskId = (int) $attachment->task_id;
        $boardId = (int) ($task?->board_id ?? 0);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        if ($task) {
            $activity->attachment($task, (int) $request->user()->id, $attachmentId, $filename, 'removed');
        }

        if ($boardId) {
            AttachmentRemoved::dispatch($attachmentId, $taskId, $boardId, (int) $request->user()->id);
        }

        return response()->noContent();
    }
}
