<div class="container-fluid py-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-people-fill text-primary me-2"></i> Manage Staff
            </h3>
            <p class="text-muted mb-0">Manage staff members, credentials, and basic salary profiles</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.staff-salary') }}" class="btn btn-outline-success">
                <i class="bi bi-cash-stack me-1"></i> Salary Management
            </a>
            @if(auth()->user()->hasPermission('menu_people_staff_add'))
            <button class="btn btn-primary" wire:click="createStaff">
                <i class="bi bi-plus-lg me-1"></i> Create Staff
            </button>
            @endif
        </div>
    </div>

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if (session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    {{-- Staff List --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i> Staff List
                </h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="text-sm text-muted fw-medium mb-0">Show</label>
                <select wire:model.live="perPage" class="form-select form-select-sm" style="width: 80px;">
                    <option value="30">30</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="all">All</option>
                </select>
                <span class="text-sm text-muted">entries</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Staff Name</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>Basic Salary</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staffs as $staff)
                        @php
                            $userDetail = $staff->userDetail;
                        @endphp
                        <tr>
                            <td class="ps-4 fw-semibold text-muted">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2 bg-primary-subtle text-primary fw-bold">
                                        {{ strtoupper(substr($staff->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $staff->name ?? '-' }}</div>
                                        <small class="text-muted">{{ $userDetail && $userDetail->address ? Str::limit($userDetail->address, 25) : 'No address set' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <i class="bi bi-telephone text-muted me-1"></i> {{ $staff->contact ?? '-' }}
                            </td>
                            <td>
                                <i class="bi bi-envelope text-muted me-1"></i> {{ $staff->email ?? '-' }}
                            </td>
                            <td>
                                @if($userDetail && $userDetail->gender)
                                    <span class="badge bg-light text-dark border text-capitalize">{{ $userDetail->gender }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($userDetail && $userDetail->basic_salary !== null)
                                    <span class="fw-bold text-success">Rs. {{ number_format($userDetail->basic_salary, 2) }}</span>
                                @else
                                    <span class="text-muted">Not Set</span>
                                @endif
                            </td>
                            <td>
                                @if($userDetail && $userDetail->status === 'active')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">
                                        <i class="bi bi-dash-circle-fill me-1"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear-fill me-1"></i> Actions
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <button class="dropdown-item" wire:click="viewDetails({{ $staff->id }})">
                                                <i class="bi bi-eye text-info me-2"></i> View Details
                                            </button>
                                        </li>
                                        @if(auth()->user()->hasPermission('menu_people_staff_edit'))
                                        <li>
                                            <button class="dropdown-item" wire:click="editStaff({{ $staff->id }})">
                                                <i class="bi bi-pencil text-primary me-2"></i> Edit Staff
                                            </button>
                                        </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.staff-salary', ['search' => $staff->name]) }}">
                                                <i class="bi bi-wallet2 text-success me-2"></i> Manage Salary
                                            </a>
                                        </li>
                                        @if(auth()->user()->hasPermission('menu_people_staff_delete'))
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item text-danger" wire:click="confirmDelete({{ $staff->id }})">
                                                <i class="bi bi-trash text-danger me-2"></i> Delete Staff
                                            </button>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-people display-4 d-block mb-2 text-muted"></i>
                                <span class="fs-6">No staff members found</span>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top py-3">
                <div class="d-flex justify-content-center">
                    {{ $staffs->links('livewire.custom-pagination') }}
                </div>
            </div>
        </div>
    </div>

    {{-- View Details Modal --}}
    @if($showViewModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-badge-fill me-2"></i> Staff Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="avatar-large mx-auto mb-2 bg-primary-subtle text-primary fw-bold fs-3">
                            {{ strtoupper(substr($viewUserDetail['name'] ?? 'S', 0, 1)) }}
                        </div>
                        <h4 class="fw-bold mb-1">{{ $viewUserDetail['name'] ?? '-' }}</h4>
                        <span class="badge bg-primary-subtle text-primary px-3 py-1">Staff Member</span>
                    </div>

                    <div class="list-group list-group-flush border rounded-3">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="bi bi-telephone me-2"></i> Contact Number</span>
                            <span class="fw-semibold">{{ $viewUserDetail['contact'] ?? '-' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="bi bi-envelope me-2"></i> Email Address</span>
                            <span class="fw-semibold">{{ $viewUserDetail['email'] ?? '-' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="bi bi-gender-ambiguous me-2"></i> Gender</span>
                            <span class="fw-semibold">{{ $viewUserDetail['gender'] ?? '-' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="bi bi-cash me-2"></i> Basic Salary</span>
                            <span class="fw-bold text-success fs-6">{{ $viewUserDetail['basic_salary'] ?? '-' }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="bi bi-activity me-2"></i> Status</span>
                            <span class="badge {{ ($viewUserDetail['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($viewUserDetail['status'] ?? 'active') }}
                            </span>
                        </div>
                        <div class="list-group-item py-2">
                            <div class="text-muted mb-1"><i class="bi bi-geo-alt me-2"></i> Address</div>
                            <div class="fw-semibold ps-4">{{ $viewUserDetail['address'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary w-100" wire:click="closeModal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Create Staff Modal --}}
    @if($showCreateModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-person-plus-fill me-2"></i> Create New Staff Member
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <form wire:submit.prevent="saveStaff" autocomplete="off">
                        <div class="row g-3">
                            {{-- Name --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Staff Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Full name of staff member" required>
                                @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Contact Number --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('contactNumber') is-invalid @enderror"
                                    wire:model="contactNumber" placeholder="e.g. 0771234567" required>
                                @error('contactNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    wire:model="email" placeholder="staff@example.com" required>
                                @error('email') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Gender --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Gender</label>
                                <select class="form-select @error('gender') is-invalid @enderror" wire:model="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('gender') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Basic Salary --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Basic Salary (Rs.) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rs.</span>
                                    <input type="number" step="0.01" min="0" class="form-control @error('basic_salary') is-invalid @enderror"
                                        wire:model="basic_salary" placeholder="50000.00" required>
                                </div>
                                @error('basic_salary') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Address --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror"
                                    wire:model="address" placeholder="Residential address" rows="1"></textarea>
                                @error('address') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Password --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        wire:model="password" placeholder="Minimum 8 characters" required
                                        id="createPassword" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('createPassword')">
                                        <i class="bi bi-eye" id="createPasswordToggleIcon"></i>
                                    </button>
                                </div>
                                @error('password') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Confirm Password --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('confirmPassword') is-invalid @enderror"
                                        wire:model="confirmPassword" placeholder="Re-enter password" required
                                        id="createConfirmPassword" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('createConfirmPassword')">
                                        <i class="bi bi-eye" id="createConfirmPasswordToggleIcon"></i>
                                    </button>
                                </div>
                                @error('confirmPassword') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-light" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                                <span wire:loading.remove><i class="bi bi-check2-circle me-1"></i> Save Staff Member</span>
                                <span wire:loading><i class="spinner-border spinner-border-sm me-1"></i> Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Staff Modal --}}
    @if($showEditModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i> Edit Staff Member
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <form wire:submit.prevent="updateStaff" autocomplete="off">
                        <div class="row g-3">
                            {{-- Name --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Staff Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('editName') is-invalid @enderror"
                                    wire:model="editName" required>
                                @error('editName') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Contact Number --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('editContactNumber') is-invalid @enderror"
                                    wire:model="editContactNumber" required>
                                @error('editContactNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Email --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('editEmail') is-invalid @enderror"
                                    wire:model="editEmail" required>
                                @error('editEmail') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Gender --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Gender</label>
                                <select class="form-select @error('editGender') is-invalid @enderror" wire:model="editGender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('editGender') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Basic Salary --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Basic Salary (Rs.) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rs.</span>
                                    <input type="number" step="0.01" min="0" class="form-control @error('editBasicSalary') is-invalid @enderror"
                                        wire:model="editBasicSalary" placeholder="50000.00" required>
                                </div>
                                @error('editBasicSalary') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Status --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('editStatus') is-invalid @enderror" wire:model="editStatus" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                @error('editStatus') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Address --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea class="form-control @error('editAddress') is-invalid @enderror"
                                    wire:model="editAddress" placeholder="Residential address" rows="2"></textarea>
                                @error('editAddress') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Password (optional on edit) --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">New Password <small class="text-muted fw-normal">(leave blank to keep current)</small></label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('editPassword') is-invalid @enderror"
                                        wire:model="editPassword" placeholder="Enter new password"
                                        id="editPassword" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('editPassword')">
                                        <i class="bi bi-eye" id="editPasswordToggleIcon"></i>
                                    </button>
                                </div>
                                @error('editPassword') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>

                            {{-- Confirm Password --}}
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control @error('editConfirmPassword') is-invalid @enderror"
                                        wire:model="editConfirmPassword" placeholder="Confirm new password"
                                        id="editConfirmPassword" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('editConfirmPassword')">
                                        <i class="bi bi-eye" id="editConfirmPasswordToggleIcon"></i>
                                    </button>
                                </div>
                                @error('editConfirmPassword') <span class="text-danger small">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-light" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                                <span wire:loading.remove><i class="bi bi-check2-circle me-1"></i> Update Staff Member</span>
                                <span wire:loading><i class="spinner-border spinner-border-sm me-1"></i> Updating...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="cancelDelete"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <i class="bi bi-person-x text-danger display-3 mb-3 d-block"></i>
                    <h5 class="fw-bold mb-2">Are you sure you want to delete this staff member?</h5>
                    <p class="text-muted mb-0">This action will remove the staff profile, login access, and related records.</p>
                </div>
                <div class="modal-footer justify-content-center bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" wire:click="cancelDelete">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" wire:click="deleteStaff" wire:loading.attr="disabled">
                        <span wire:loading.remove><i class="bi bi-trash me-1"></i> Delete Staff</span>
                        <span wire:loading><i class="spinner-border spinner-border-sm me-1"></i> Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .avatar-circle {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-large {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@push('scripts')
<script>
    function togglePasswordVisibility(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(inputId + 'ToggleIcon');

        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleIcon.classList.remove("bi-eye");
            toggleIcon.classList.add("bi-eye-slash");
        } else {
            passwordInput.type = "password";
            toggleIcon.classList.remove("bi-eye-slash");
            toggleIcon.classList.add("bi-eye");
        }
    }
</script>
@endpush