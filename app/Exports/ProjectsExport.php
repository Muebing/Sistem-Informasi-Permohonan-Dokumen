<?php

namespace App\Exports;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/** @implements WithMapping<Project> */
class ProjectsExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Builder<Project>  $query
     */
    public function __construct(
        private readonly Builder $query,
    ) {}

    /**
     * @return Builder<Project>
     */
    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Kode Project',
            'Nama Project',
            'Pemohon',
            'Status',
            'Tanggal Submit',
            'Tanggal Dibuat',
        ];
    }

    /**
     * @param  Project  $project
     * @return array<int, string>
     */
    public function map($project): array
    {
        return [
            $project->project_code,
            $project->project_name,
            $project->applicant->name ?? '-',
            ucfirst((string) str_replace('_', ' ', $project->status->value)),
            $project->submitted_at?->format('d/m/Y H:i') ?? '-',
            $project->created_at->format('d/m/Y H:i'),
        ];
    }
}
