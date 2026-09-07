<?php

namespace App\Livewire\Admin;

use Exception;
use App\Models\User;
use App\Models\UserDetail;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Hash;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title('Manage Staff')]
class ManageStaff extends Component
{
    use WithDynamicLayout;

    public $viewUserDetail = [];

    // Create Staff fields (only requested fields)
    public $name;
    public $contactNumber;
    public $email;
    public $gender = '';
    public $basic_salary;
    public $address;
    public $password;
    public $confirmPassword;

    // Edit Staff fields
    public $editStaffId;
    public $editName;
    public $editContactNumber;
    public $editEmail;
    public $editGender = '';
    public $editBasicSalary;
    public $editAddress;
    public $editPassword;
    public $editConfirmPassword;
    public $editStatus = 'active';

    public $deleteId;
    public $showEditModal = false;
    public $showCreateModal = false;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $perPage = 30;

    public function render()
    {
        $query = User::where('role', 'staff')->with('userDetail')->latest();

        if ($this->perPage === 'all') {
            $totalRows = (clone $query)->count();
            $staffs = $query->paginate($totalRows > 0 ? $totalRows : 1);
        } else {
            $staffs = $query->paginate((int) $this->perPage);
        }
        return view('livewire.admin.manage-staff', [
            'staffs' => $staffs,
        ])->layout($this->layout);
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    /** ----------------------------
     * View Staff Details
     * ---------------------------- */
    public function viewDetails($id)
    {
        $user = User::with('userDetail')->find($id);
        if (!$user) {
            $this->js("Swal.fire('Error!', 'Staff Not Found', 'error')");
            return;
        }

        $userDetail = $user->userDetail;
        $this->viewUserDetail = [
            'name' => $user->name,
            'contact' => $user->contact,
            'email' => $user->email,
            'role' => $user->role,
            'gender' => $userDetail ? ucfirst($userDetail->gender ?? '-') : '-',
            'basic_salary' => $userDetail && $userDetail->basic_salary !== null ? 'Rs. ' . number_format($userDetail->basic_salary, 2) : '-',
            'address' => $userDetail && $userDetail->address ? $userDetail->address : '-',
            'status' => $userDetail ? $userDetail->status : 'active',
        ];

        $this->showViewModal = true;
    }

    /** ----------------------------
     * Create Staff
     * ---------------------------- */
    public function createStaff()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function resetForm()
    {
        $this->reset([
            'name',
            'contactNumber',
            'email',
            'gender',
            'basic_salary',
            'address',
            'password',
            'confirmPassword',
            'editStaffId',
            'editName',
            'editContactNumber',
            'editEmail',
            'editGender',
            'editBasicSalary',
            'editAddress',
            'editPassword',
            'editConfirmPassword',
            'editStatus'
        ]);
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    public function saveStaff()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:15',
            'email' => 'required|email|unique:users,email',
            'gender' => 'nullable|in:male,female,other',
            'basic_salary' => 'required|numeric|min:0',
            'address' => 'nullable|string',
            'password' => 'required|min:8',
            'confirmPassword' => 'required|min:8|same:password',
        ]);

        try {
            $user = User::create([
                'name' => $this->name,
                'contact' => $this->contactNumber,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => 'staff',
            ]);

            UserDetail::create([
                'user_id' => $user->id,
                'gender' => $this->gender ?: null,
                'basic_salary' => $this->basic_salary,
                'address' => $this->address ?: null,
                'status' => 'active',
                'work_type' => 'monthly',
                'dob' => null,
                'age' => null,
                'nic_num' => null,
                'work_role' => null,
                'department' => null,
                'join_date' => null,
                'fingerprint_id' => null,
                'allowance' => null,
                'user_image' => null,
                'description' => null,
            ]);

            $this->js("Swal.fire('Success!', 'Staff Created Successfully', 'success')");
            $this->closeModal();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . addslashes($e->getMessage()) . "', 'error')");
        }
    }

    /** ----------------------------
     * Edit Staff
     * ---------------------------- */
    public function editStaff($id)
    {
        $user = User::with('userDetail')->find($id);
        if (!$user) {
            $this->js("Swal.fire('Error!', 'Staff Not Found', 'error')");
            return;
        }

        $userDetail = $user->userDetail;

        $this->editStaffId = $user->id;
        $this->editName = $user->name;
        $this->editContactNumber = $user->contact;
        $this->editEmail = $user->email;
        $this->editGender = $userDetail ? ($userDetail->gender ?? '') : '';
        $this->editBasicSalary = $userDetail ? $userDetail->basic_salary : null;
        $this->editAddress = $userDetail ? $userDetail->address : '';
        $this->editStatus = $userDetail ? $userDetail->status : 'active';
        $this->editPassword = '';
        $this->editConfirmPassword = '';

        $this->showEditModal = true;
    }

    public function updateStaff()
    {
        $validationRules = [
            'editName' => 'required|string|max:255',
            'editContactNumber' => 'required|string|max:15',
            'editEmail' => 'required|email|unique:users,email,' . $this->editStaffId,
            'editGender' => 'nullable|in:male,female,other',
            'editBasicSalary' => 'required|numeric|min:0',
            'editAddress' => 'nullable|string',
            'editStatus' => 'required|in:active,inactive',
        ];

        // Only validate password if it's provided
        if (!empty($this->editPassword)) {
            $validationRules['editPassword'] = 'required|min:8';
            $validationRules['editConfirmPassword'] = 'required|same:editPassword';
        }

        $this->validate($validationRules);

        try {
            $user = User::find($this->editStaffId);
            if ($user) {
                $user->name = $this->editName;
                $user->contact = $this->editContactNumber;
                $user->email = $this->editEmail;

                if (!empty($this->editPassword)) {
                    $user->password = Hash::make($this->editPassword);
                }

                $user->save();

                UserDetail::updateOrCreate(
                    ['user_id' => $this->editStaffId],
                    [
                        'gender' => $this->editGender ?: null,
                        'basic_salary' => $this->editBasicSalary,
                        'address' => $this->editAddress ?: null,
                        'status' => $this->editStatus,
                    ]
                );

                $this->js("Swal.fire('Success!', 'Staff Updated Successfully', 'success')");
                $this->closeModal();
            } else {
                $this->js("Swal.fire('Error!', 'Staff Not Found', 'error')");
            }
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . addslashes($e->getMessage()) . "', 'error')");
        }
    }

    /** ----------------------------
     * Delete Staff
     * ---------------------------- */
    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
    }

    public function deleteStaff()
    {
        try {
            User::where('id', $this->deleteId)->delete();
            $this->js("Swal.fire('Success!', 'Staff deleted successfully.', 'success')");
            $this->cancelDelete();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . addslashes($e->getMessage()) . "', 'error')");
        }
    }
}
