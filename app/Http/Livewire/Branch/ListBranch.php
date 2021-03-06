<?php

namespace App\Http\Livewire\Branch;

use App\Models\Branch;
use Livewire\Component;
use Illuminate\Http\Request;
use Livewire\WithPagination;

class ListBranch extends Component
{

    use WithPagination;
    protected $listeners = ['refreshBranch' => '$refresh'];
    protected $paginationTheme = 'bootstrap';
    
    public $search;
    public $page = 1;
    public $per_page = 10;

    public $bulkSelectAll = false;
    public $bulk_select = [];

    public $delete_id = null;
    public $delete_single_item = true;

    public $edit_branch_id = null;
    public $edit_branch_name;
    public $edit_branch_location;
    public $edit_branch_description;

    public $edit_branchs = [];


    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];


    protected $rules = [
        'edit_branchs.*.name' => 'required|min:2|max:255',
        'edit_branchs.*.location' => 'nullable|min:2|max:255',
        'edit_branchs.*.description' => 'nullable|max:255',
    ];


    


    public function updatedBulkSelectAll($value)
    {
        if($value){
            $this->bulk_select = Branch::pluck('id');
        }else{
            $this->bulk_select = [];
        }
    }

    public function deleteItem()
    {
        if($this->delete_single_item == true) {
            if($this->delete_id == null || $this->delete_id == ''){
                session()->flash('error','Something went to wrong!!,Please try agian');

            }else{
                Branch::find($this->delete_id)->delete();
                session()->flash('success','Branch Item has been successfully Deleted!!');
                $this->emit('refreshBranch');
                $this->delete_id = null;
            }
        }else{
            if($this->bulk_select == [] || $this->bulk_select == '' || $this->bulk_select == null){
                session()->flash('error','Something went to wrong!!,Please try agian.');

            }else{
                Branch::destroy($this->bulk_select);
                session()->flash('success','Branch Items has been successfully Deleted!!');
                $this->emit('refreshBranch');
                $this->bulk_select = [];
            }
        }    
        
    }


    public function editItem($id)
    {
        if($id == null || $id == '' || $id <= 0){
            session()->flash('error','Something went to wrong!!,Please try agian');
        }else{
            $branch = Branch::find($id);
            $this->edit_branch_id = $branch->id;
            $this->edit_branch_name = $branch->name;
            $this->edit_branch_location = $branch->location;
            $this->edit_branch_description = $branch->description;
        }
        
    }




    public function updateItem($id)
    {
        $this->validate([
            "edit_branch_name" => "required|min:2|max:255|unique:branches,name,$id",
            "edit_branch_location" => "required|min:2|max:255",
            "edit_branch_description" => "nullable|max:255",
        ]);
        
        if($id == null || $id == '' || $id <= 0){
            session()->flash('error','Something went to wrong!!,Please try agian');
        }else{
            $branch = Branch::find($id);
            $branch->name = $this->edit_branch_name;
            $branch->location = $this->edit_branch_location;
            $branch->description = $this->edit_branch_description;
            if($branch->update()){
                session()->flash('success','Branch Item has been successfully updated!!');
                $this->emit('refreshBranch');
                // $this->edit_department_id = null;
            }else{
                session()->flash('error','Something went to wrong!!,Please try agian');
            }
        }
    }


    public function editItems()
    {
        $this->edit_branchs = Branch::whereIn('id',$this->bulk_select)->get();
    }



    public function updateItems()
    {
       $this->validate();
       foreach($this->edit_branchs as $edit_branch){
           $branch = Branch::find($edit_branch->id);
           $branch->name = $edit_branch->name;
           $branch->location = $edit_branch->location;
           $branch->description = $edit_branch->description;
           $branch->update();
       }

       session()->flash('success','Branch Items has been successfully updated!!');
       $this->emit('refreshBranch');
       $this->bulk_select = [];
       $this->edit_branchs = [];
       $this->bulkSelectAll = false;
    }


    public function exportItems()
    {
        if($this->bulk_select == [] || $this->bulk_select == '' || $this->bulk_select == null){
            session()->flash('error','Something went to wrong!!,Please try agian.');

        }else{
            return response()->streamDownload(function(){
                echo Branch::whereKey($this->bulk_select)->toCsv();
            },'branchs.csv');

           $this->bulk_select = [];
        }
    }


    public function render()
    {
        $branchs = Branch::latest()->where('name', 'like', '%'.$this->search.'%')->paginate($this->per_page);
        return view('livewire.branch.list-branch',['branchs' => $branchs]);
    }
}
