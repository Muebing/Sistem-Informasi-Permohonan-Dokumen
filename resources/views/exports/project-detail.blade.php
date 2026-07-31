<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $project->project_code }} — Detail Permohonan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .meta { color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; }
        th { background: #f3f4f6; width: 30%; }
        .notes { background: #f9fafb; padding: 6px; border-radius: 4px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $project->project_name }}</h1>
    <div class="meta">{{ $project->project_code }} — Status: {{ ucfirst(str_replace('_', ' ', $project->status->value)) }}</div>

    <h2>Informasi</h2>
    <table>
        <tr><th>Kode Project</th><td>{{ $project->project_code }}</td></tr>
        <tr><th>Pemohon</th><td>{{ $project->applicant->name }} ({{ $project->applicant->email }})</td></tr>
        <tr><th>Deskripsi</th><td>{{ $project->description ?? '-' }}</td></tr>
        <tr><th>Dibuat</th><td>{{ $project->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Submitted</th><td>{{ $project->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
    </table>

    <h2>Dokumen ({{ $project->documents->count() }})</h2>
    <table>
        <thead>
            <tr><th>No</th><th>Nama File</th><th>Ukuran</th><th>Tipe</th><th>Diunggah</th></tr>
        </thead>
        <tbody>
            @forelse ($project->documents as $index => $document)
                <tr>
                    <td class="right">{{ $index + 1 }}</td>
                    <td>{{ $document->original_name }}</td>
                    <td>{{ number_format($document->size / 1024, 1) }} KB</td>
                    <td>{{ $document->mime_type }}</td>
                    <td>{{ $document->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada dokumen.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Riwayat Penilaian</h2>
    @forelse ($project->reviews as $review)
        <div style="margin-bottom: 8px;">
            <strong>{{ $review->reviewer->name }}</strong> — {{ ucfirst(str_replace('_', ' ', $review->status->value)) }}
            <div class="meta">{{ $review->created_at->format('d/m/Y H:i') }}</div>
            @if ($review->notes)
                <div class="notes">{{ $review->notes }}</div>
            @endif
        </div>
    @empty
        <p>Belum ada penilaian.</p>
    @endforelse

    <h2>Approval Logs</h2>
    <table>
        <thead>
            <tr><th>No</th><th>Aksi</th><th>Dari</th><th>Ke</th><th>Oleh</th><th>Waktu</th></tr>
        </thead>
        <tbody>
            @forelse ($project->approvalLogs as $index => $log)
                <tr>
                    <td class="right">{{ $index + 1 }}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($log->action)) }}</td>
                    <td>{{ $log->old_status ?? '-' }}</td>
                    <td>{{ $log->new_status ?? '-' }}</td>
                    <td>{{ $log->user?->name ?? '-' }}</td>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Tidak ada log.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
