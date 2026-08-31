<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCertificateRequest;
use App\Models\Certification;
use App\Models\TrainingRecord;
use App\Models\Worker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class WorkerCertificateController extends Controller
{
    /**
     * Upload a certificate for a worker, optionally attaching it to the training
     * record it proves. Re-uploading against the same record replaces the file.
     */
    public function store(StoreCertificateRequest $request, Worker $worker): RedirectResponse
    {
        $this->authorize('update', $worker);

        $data = $request->validated();
        $file = $request->file('file');

        $trainingRecord = isset($data['training_record_id'])
            ? TrainingRecord::where('worker_id', $worker->id)->findOrFail($data['training_record_id'])
            : null;

        $certification = $trainingRecord?->certification ?? new Certification([
            'worker_id' => $worker->id,
            'company_id' => $worker->company_id,
        ]);

        // Replacing a certificate should not leave the previous file behind.
        if ($certification->file_path) {
            Storage::disk('public')->delete($certification->file_path);
        }

        $certification->fill([
            'worker_id' => $worker->id,
            'company_id' => $worker->company_id,
            'name' => $data['name'] ?? $trainingRecord?->course_name ?? $file->getClientOriginalName(),
            'issuer' => $data['issuer'] ?? $trainingRecord?->provider,
            'certificate_number' => $data['certificate_number'] ?? $certification->certificate_number,
            'issued_at' => $data['issued_at'] ?? $trainingRecord?->completed_at,
            'expires_at' => $data['expires_at'] ?? $trainingRecord?->expires_at,
            'status' => 'valid',
            'file_path' => $file->store('certificates', 'public'),
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
        ])->save();

        $trainingRecord?->update(['certification_id' => $certification->id]);

        return back()->with('success', 'Certificate uploaded.');
    }

    public function destroy(Worker $worker, Certification $certification): RedirectResponse
    {
        $this->authorize('update', $worker);
        abort_unless($certification->worker_id === $worker->id, 404);

        if ($certification->file_path) {
            Storage::disk('public')->delete($certification->file_path);
        }

        $certification->trainingRecord?->update(['certification_id' => null]);
        $certification->delete();

        return back()->with('success', 'Certificate removed.');
    }
}
