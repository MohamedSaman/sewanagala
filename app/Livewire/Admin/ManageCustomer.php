<?php

namespace App\Livewire\Admin;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;
use Exception;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Livewire\Concerns\WithDynamicLayout;

#[Title('Manage Customer')]
class ManageCustomer extends Component
{
    use WithDynamicLayout, WithPagination;

    public $name;
    public $contactNumber;
    public $address;
    public $email;
    public $customerType;
    public $businessName;
    public $openingBalance = 0;

    public $editCustomerId;
    public $editName;
    public $editContactNumber;
    public $editAddress;
    public $editEmail;
    public $editCustomerType;
    public $editBusinessName;
    public $editOpeningBalance = 0;

    public $deleteId;
    public $showEditModal = false;
    public $showCreateModal = false;
    public $showDeleteModal = false;
    public $showViewModal = false;
    public $viewCustomerDetail = [];
    public $perPage = 30;
    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Customer::when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('phone', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->orWhere('business_name', 'like', '%' . $this->search . '%');
        })
            ->orderBy('name', 'asc');

        if ($this->perPage === 'all') {
            $totalRows = (clone $query)->count();
            $customers = $query->paginate($totalRows > 0 ? $totalRows : 1);
        } else {
            $customers = $query->paginate((int) $this->perPage);
        }

        return view('livewire.admin.manage-customer', [
            'customers' => $customers,
        ])->layout($this->layout);
    }

    /** ----------------------------
     * Create Customer
     * ---------------------------- */
    public function createCustomer()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function resetForm()
    {
        $this->reset([
            'name',
            'contactNumber',
            'address',
            'email',
            'customerType',
            'businessName',
            'openingBalance',
            'editCustomerId',
            'editName',
            'editContactNumber',
            'editAddress',
            'editEmail',
            'editCustomerType',
            'editBusinessName',
            'editOpeningBalance'
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

    /** ----------------------------
     * View Customer Details
     * ---------------------------- */
    public function viewDetails($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            $this->js("Swal.fire('Error!', 'Customer Not Found', 'error')");
            return;
        }

        $this->viewCustomerDetail = [
            'name' => $customer->name,
            'business_name' => $customer->business_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'type' => $customer->type,
            'address' => $customer->address,
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at,
            'opening_balance' => $customer->opening_balance,
        ];

        $this->showViewModal = true;
    }

    public function saveCustomer()
    {
        $this->validate([
            'name' => 'required',
            'customerType' => 'required',
            'contactNumber' => 'nullable | max:30',
            'address' => 'nullable',
            'email' => 'nullable|email|unique:customers,email',
            'businessName' => 'nullable',
            'openingBalance' => 'nullable|numeric|min:0',
        ]);

        try {
            Customer::create([
                'name' => $this->name,
                'phone' => $this->contactNumber,
                'address' => $this->address,
                'email' => $this->email,
                'type' => $this->customerType,
                'business_name' => $this->businessName,
                'opening_balance' => $this->openingBalance ?: 0,
            ]);

            $this->js("Swal.fire('Success!', 'Customer Created Successfully', 'success')");
            $this->closeModal();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . $e->getMessage() . "', 'error')");
        }
    }

    /** ----------------------------
     * Edit Customer
     * ---------------------------- */
    public function editCustomer($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            $this->js("Swal.fire('Error!', 'Customer not found.', 'error')");
            return;
        }

        $this->editCustomerId = $customer->id;
        $this->editName = $customer->name;
        $this->editContactNumber = $customer->phone;
        $this->editBusinessName = $customer->business_name;
        $this->editCustomerType = $customer->type;
        $this->editAddress = $customer->address;
        $this->editEmail = $customer->email;
        $this->editOpeningBalance = $customer->opening_balance;

        $this->showEditModal = true;
    }

    public function updateCustomer()
    {
        $this->validate([
            'editName' => 'required',
            'editCustomerType' => 'required',
            'editBusinessName' => 'nullable',
            'editContactNumber' => 'nullable | max:30',
            'editAddress' => 'nullable',
            'editEmail' => 'nullable|email|unique:customers,email,' . $this->editCustomerId,
            'editOpeningBalance' => 'nullable|numeric|min:0',
        ]);

        try {
            $customer = Customer::find($this->editCustomerId);
            if (!$customer) {
                $this->js("Swal.fire('Error!', 'Customer not found.', 'error')");
                return;
            }

            $customer->update([
                'name' => $this->editName,
                'phone' => $this->editContactNumber,
                'business_name' => $this->editBusinessName,
                'type' => $this->editCustomerType,
                'address' => $this->editAddress,
                'email' => $this->editEmail,
                'opening_balance' => $this->editOpeningBalance ?: 0,
            ]);

            $this->js("Swal.fire('Success!', 'Customer Updated Successfully', 'success')");
            $this->closeModal();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . $e->getMessage() . "', 'error')");
        }
    }

    /** ----------------------------
     * Delete Customer
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

    public function goToCustomerTransactions($customerId)
    {
        $prefix = auth()->user()->role === 'admin' ? 'admin.' : 'staff.';
        return redirect()->route($prefix . 'customer-transactions', $customerId);
    }

    public function deleteCustomer()
    {
        try {
            Customer::where('id', $this->deleteId)->delete();
            $this->js("Swal.fire('Success!', 'Customer deleted successfully.', 'success')");
            $this->dispatch('refreshPage');
            $this->cancelDelete();
        } catch (Exception $e) {
            $this->js("Swal.fire('Error!', '" . $e->getMessage() . "', 'error')");
        }
    }
}
