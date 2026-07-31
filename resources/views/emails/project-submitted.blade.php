@component('mail::message')
# Permohonan Baru Menunggu Review

Permohonan dokumen **{{ $project->project_name }}** ({{ $project->project_code }})
yang diajukan oleh **{{ $project->applicant->name }}** telah masuk dan menunggu
penilaian Anda.

@component('mail::button', ['url' => rtrim((string) config('app.frontend_url'), '/').'/#/projects/'.$project->id])
Buka Permohonan
@endcomponent

Terima kasih,
<br>
{{ config('app.name') }}
@endcomponent
