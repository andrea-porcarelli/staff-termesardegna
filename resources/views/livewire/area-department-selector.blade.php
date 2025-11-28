<div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="area_id" class="form-label">Area <span class="text-danger">*</span></label>
            <div class="input-group">
                <select wire:model.live="selectedAreaId" class="form-select @error('selectedAreaId') is-invalid @enderror" id="area_id" name="area_id" required>
                    <option value="">Seleziona un'area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary" wire:click="toggleNewAreaForm" title="Aggiungi nuova area">
                    <i class="bi bi-plus-circle"></i>
                </button>
            </div>
            @error('selectedAreaId')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6 mb-3">
            <label for="department_id" class="form-label">Reparto <span class="text-danger">*</span></label>
            <div class="input-group">
                <select wire:model.live="selectedDepartmentId" class="form-select @error('selectedDepartmentId') is-invalid @enderror" id="department_id" name="department_id" required>
                    <option value="">
                        @if($selectedAreaId)
                            Seleziona un reparto
                        @else
                            Prima seleziona un'area
                        @endif
                    </option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary" wire:click="toggleNewDepartmentForm" title="Aggiungi nuovo reparto" @disabled(!$selectedAreaId)>
                    <i class="bi bi-plus-circle"></i>
                </button>
            </div>
            @error('selectedDepartmentId')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
    </div>

    @if($showNewAreaForm)
        <div class="mb-3 p-2 bg-light border rounded">
            <div class="input-group input-group-sm">
                <input type="text" wire:model="newAreaName" class="form-control @error('newAreaName') is-invalid @enderror" id="newAreaName" placeholder="Nome nuova area...">
                <button type="button" wire:click="saveNewArea" class="btn btn-success" title="Salva">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button type="button" wire:click="toggleNewAreaForm" class="btn btn-secondary" title="Annulla">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            @error('newAreaName')
                <small class="text-danger d-block mt-1">{{ $message }}</small>
            @enderror
        </div>
    @endif

    @if($showNewDepartmentForm)
        <div class="mb-3 p-2 bg-light border rounded">
            <div class="input-group input-group-sm">
                <input type="text" wire:model="newDepartmentName" class="form-control @error('newDepartmentName') is-invalid @enderror" id="newDepartmentName" placeholder="Nome nuovo reparto...">
                <button type="button" wire:click="saveNewDepartment" class="btn btn-success" title="Salva">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button type="button" wire:click="toggleNewDepartmentForm" class="btn btn-secondary" title="Annulla">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            @error('newDepartmentName')
                <small class="text-danger d-block mt-1">{{ $message }}</small>
            @enderror
        </div>
    @endif

    <input type="hidden" name="area_id" value="{{ $selectedAreaId }}">
    <input type="hidden" name="department_id" value="{{ $selectedDepartmentId }}">
</div>
