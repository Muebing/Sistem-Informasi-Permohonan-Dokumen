<?php

namespace App\Http\Controllers\Api;

use App\Exports\ProjectsExport;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Repositories\ProjectRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly ProjectRepository $repository,
    ) {}

    public function excel(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Project::class);

        $query = $this->buildExportQuery($request);

        return Excel::download(
            new ProjectsExport($query),
            'siperdo-projects-'.now()->format('Ymd-His').'.xlsx',
        );
    }

    public function pdf(Request $request): Response
    {
        $this->authorize('export', Project::class);

        $query = $this->buildExportQuery($request)->limit(500);

        $pdf = Pdf::loadView('exports.projects', [
            'projects' => $query->get(),
            'generated_at' => now(),
            'filters' => $this->filters($request),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('siperdo-projects-'.now()->format('Ymd-His').'.pdf');
    }

    public function projectPdf(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load([
            'applicant:id,name,email',
            'documents',
            'reviews.reviewer:id,name',
            'approvalLogs.user:id,name',
        ]);

        $pdf = Pdf::loadView('exports.project-detail', [
            'project' => $project,
        ])->setPaper('a4');

        return $pdf->download('siperdo-'.$project->project_code.'.pdf');
    }

    /**
     * @return Builder<Project>
     */
    private function buildExportQuery(Request $request): Builder
    {
        $query = $this->repository->query($this->filters($request));

        if ($request->user()->hasRole('applicant')) {
            $query->where('applicant_id', $request->user()->id);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->only([
            'search', 'status', 'applicant_id', 'reviewer_id', 'date_from', 'date_to', 'sort',
        ]);
    }
}
