@component('mail::message')
# Status Permohonan Diperbarui

Permohonan dokumen **{{ $project->project_name }}** ({{ $project->project_code }})
saat ini berstatus:

### **{{ $status->label() }}**

@if ($notes)
**Catatan penilai:**
> {{ $notes }}
@else
Tidak ada catatan tambahan dari penilai.
@endif

@if ($status === \App\Enums\ProjectStatus::Revision)
Silakan lengkapi perbaikan yang diminta lalu ajukan kembali permohonan Anda.
@elseif ($status === \App\Enums\ProjectStatus::Rejected)
Jika Anda memiliki pertanyaan, silakan hubungi petugas terkait.
@endif

Terima kasih,
<br>
{{ config('app.name') }}
@endcomponent
