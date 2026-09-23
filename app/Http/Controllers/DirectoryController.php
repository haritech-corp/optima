<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Generic CRUD for CRM directory entities (community, KOL, media, vendor,
 * campaign, case study). Subclasses only declare their model, routes, columns,
 * form fields and validation rules.
 */
abstract class DirectoryController extends Controller
{
    /** @var class-string<Model> */
    abstract protected function model(): string;

    /** Resource route name, e.g. 'communities'. */
    abstract protected function routeName(): string;

    /** Columns shown on the index table: attribute => label. */
    abstract protected function columns(): array;

    /** Form field definitions: attribute => [label, type, options, required]. */
    abstract protected function fields(): array;

    /** Laravel validation rules. */
    abstract protected function rules(): array;

    protected string $searchColumn = 'name';

    protected bool $archivable = true;

    protected function permissionBase(): string
    {
        return 'crm';
    }

    public function __construct()
    {
        $base = $this->permissionBase().'.'.$this->entityKey();
        $this->middleware("permission:{$base}.view")->only(['index']);
        $this->middleware("permission:{$base}.create")->only(['create', 'store']);
        $this->middleware("permission:{$base}.edit")->only(['edit', 'update']);
        $this->middleware("permission:{$base}.archive")->only(['archive']);
    }

    protected function entityKey(): string
    {
        return str_replace('-', '', $this->routeName());
    }

    public function index(Request $request): View
    {
        $model = $this->model();
        $query = $model::query();

        if ($this->archivable) {
            $query->whereNull('archived_at');
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where($this->searchColumn, 'ilike', "%{$search}%");
        }

        return view('directory.index', [
            'title' => $this->pageTitle(),
            'routeName' => $this->routeName(),
            'rows' => $query->latest()->paginate(15)->withQueryString(),
            'columns' => $this->columns(),
        ]);
    }

    public function create(): View
    {
        return $this->formView('Buat');
    }

    public function store(Request $request): RedirectResponse
    {
        $record = $this->model()::create($request->validate($this->rules()));

        return redirect()->route($this->routeName().'.index')->with('status', $this->pageTitle().' berhasil dibuat (#'.$record->getKey().').');
    }

    public function edit(string $id): View
    {
        $view = $this->formView('Ubah');
        $view->with('record', $this->model()::findOrFail($id));

        return $view;
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $record = $this->model()::findOrFail($id);
        $record->update($request->validate($this->rules()));

        return redirect()->route($this->routeName().'.index')->with('status', $this->pageTitle().' berhasil diperbarui.');
    }

    public function archive(string $id): RedirectResponse
    {
        $this->model()::whereKey($id)->update(['archived_at' => now()]);

        return redirect()->route($this->routeName().'.index')->with('status', $this->pageTitle().' diarsipkan.');
    }

    private function formView(string $action): View
    {
        return view('directory.form', [
            'title' => $action.' '.$this->pageTitle(),
            'routeName' => $this->routeName(),
            'fields' => $this->fields(),
            'record' => null,
            'employees' => \App\Models\Employee::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    protected function pageTitle(): string
    {
        return ucfirst(str_replace('-', ' ', $this->routeName()));
    }

    /** Shared "PIC employee" field definition. */
    protected function employeeField(bool $required = false): array
    {
        return ['label' => 'PIC', 'type' => 'employee_select', 'required' => $required];
    }
}