<?php

namespace App\Livewire\Admin;

use App\Jobs\ProcessContentImport;
use App\Models\ContentImport;
use App\Models\User;
use App\Models\Vertical;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class ImportManager extends Component
{
    use WithFileUploads;

    public mixed $file = null;

    public ?int $verticalId = null;

    public function upload(): void
    {
        $validated = $this->validate([
            'verticalId' => ['required', 'exists:verticals,id'],
            'file' => ['required', File::types(['csv', 'json', 'xlsx', 'xls'])->max(20 * 1024)],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $originalName = $this->file->getClientOriginalName();
        $format = strtolower($this->file->getClientOriginalExtension());
        $path = $this->file->store('imports', 'local');

        $import = ContentImport::create([
            'user_id' => $user->id,
            'vertical_id' => $validated['verticalId'],
            'type' => 'questions',
            'format' => $format,
            'original_name' => $originalName,
            'stored_path' => $path,
            'status' => 'pending',
        ]);

        ProcessContentImport::dispatch($import->id);

        $this->reset(['file']);
        session()->flash('admin_message', 'Importul a fost pus în coadă.');
    }

    public function render(): View
    {
        return view('livewire.admin.import-manager', [
            'verticals' => Vertical::query()->orderBy('name')->get(),
            'imports' => ContentImport::query()->with(['vertical', 'user'])->latest()->limit(100)->get(),
        ]);
    }
}
