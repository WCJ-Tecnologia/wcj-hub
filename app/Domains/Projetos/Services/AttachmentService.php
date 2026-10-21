<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Task;
use App\Domains\Projetos\Models\TaskAttachment;
use App\Platform\Enums\AuditActionEnum;
use App\Platform\Models\User;
use App\Platform\Services\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Anexos do card (P24, P59; F3 §04; banco.md task_attachments): enviar guarda o arquivo no disco
 * privado e a linha com o nome original; remover apaga o arquivo e a linha juntos, e a FK limpa a
 * capa (set null). Exclusão de verdade (spec b3-ficha). O arquivo vai ao disco antes da linha; se a
 * transação cai, o que já foi gravado no disco sai.
 */
class AttachmentService
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return list<TaskAttachment>
     */
    public function upload(Task $task, User $actor, array $files): array
    {
        $stored = [];
        try {
            return DB::transaction(function () use ($task, $actor, $files, &$stored) {
                $created = [];
                foreach ($files as $file) {
                    $path = $file->storeAs("tasks/{$task->id}", Str::uuid().'.'.$file->extension(), TaskDetailPresenter::DISK);
                    $stored[] = $path;
                    $attachment = $task->attachments()->create([
                        'path' => $path,
                        'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'user_id' => $actor->id,
                    ]);
                    $this->audit->log(AuditActionEnum::CREATED, $attachment, null, [
                        'task_id' => $task->id, 'original_name' => $attachment->original_name, 'mime_type' => $attachment->mime_type,
                    ], $actor->id);
                    $created[] = $attachment;
                }

                return $created;
            });
        } catch (Throwable $e) {
            Storage::disk(TaskDetailPresenter::DISK)->delete($stored);
            throw $e;
        }
    }

    /** O arquivo sai depois do commit: se a linha não sai, o arquivo fica. Arquivo que já não existe não impede. */
    public function remove(TaskAttachment $attachment, User $actor): void
    {
        DB::transaction(function () use ($attachment, $actor) {
            $locked = TaskAttachment::query()->lockForUpdate()->findOrFail($attachment->id);
            $locked->delete();
            $this->audit->log(AuditActionEnum::DELETED, $locked, [
                'task_id' => $locked->task_id, 'original_name' => $locked->original_name, 'path' => $locked->path,
            ], null, $actor->id);
            DB::afterCommit(fn () => Storage::disk(TaskDetailPresenter::DISK)->delete($locked->path));
        });
    }

    /**
     * Download com o nome original (P24). Imagem abre na própria página (miniatura e capa); o resto
     * baixa. O Content-Type vem do tipo gravado, conferido pelos bytes no envio (seguranca.md §7), e
     * sempre com nosniff. Quem chama já autorizou; arquivo que sumiu do disco é 404 (B9, SEC-11).
     */
    public function download(TaskAttachment $attachment): StreamedResponse
    {
        $disk = Storage::disk(TaskDetailPresenter::DISK);
        abort_unless($disk->exists($attachment->path), 404);

        $headers = ['Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff'];

        return $attachment->isImage()
            ? $disk->response($attachment->path, $attachment->original_name, $headers)
            : $disk->download($attachment->path, $attachment->original_name, $headers);
    }
}
