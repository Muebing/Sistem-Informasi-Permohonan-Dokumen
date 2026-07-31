<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Permohonan Dokumen</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #6b7280; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>SIPERDO — Laporan Permohonan Dokumen</h1>
    <div class="meta">
        Dicetak: {{ $generated_at->format('d/m/Y H:i') }} |
        Total data: {{ number_format($projects->count()) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Project</th>
                <th>Nama Project</th>
                <th>Pemohon</th>
                <th>Status</th>
                <th>Tanggal Submit</th>
                <th>Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $index => $project)
                <tr>
                    <td class="right">{{ $index + 1 }}</td>
                    <td>{{ $project->project_code }}</td>
                    <td>{{ $project->project_name }}</td>
                    <td>{{ $project->applicant?->name ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $project->status->value)) }}</td>
                    <td>{{ $project->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>{{ $project->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
