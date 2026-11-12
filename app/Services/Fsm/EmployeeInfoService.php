<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Services\Fsm;

use App\Models\Fsm\EmployeeInfo;
use Illuminate\Support\Collection;
use Illuminate\Session\SessionManager;
use DB;
use Carbon\Carbon;
use Auth;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Yajra\DataTables\DataTables;
use App\Enums\EmployeeStatus;

class EmployeeInfoService {

    protected $session;
    protected $instance;

   
    public function __construct()
    {
        /*Session code
        ....
         here*/


    }

    /**
     * Get all the All Employee Info.
     *
     *
     * @return EmployeeInfo[]|Collection
     */
    public function getAllEmployeeInfo($data)
    
    {
        
       if(Auth::user()->hasRole('Service Provider - Admin') )
       {
        $employeeInfos =  EmployeeInfo::select('*')->where('service_provider_id',"=", Auth::user()->service_provider_id)->whereNull('deleted_at');
       }
       else
       {
        $employeeInfos =  EmployeeInfo::select('*')->whereNull('deleted_at');
       }
        return Datatables::of($employeeInfos)
            ->filter(function ($query) use ($data) {
               
                if ($data['id']){
                    $query->where('fsm.employees.id',$data['id']);
                }
                if ($data['employee_name']){
                    $query->where('fsm.employees.name','ILIKE','%'.$data['employee_name'].'%');
                }
                if ($data['employee_type']){
                    $query->where('fsm.employees.employee_type',$data['employee_type']);
                }
                if ($data['service_provider_id']){
                    $query->where('fsm.employees.service_provider_id',$data['service_provider_id']);
                }
                if ($data['status']) {
                    $query->where('status', $data['status']);                
                }
            })
            ->addColumn('action', function ($model) {
                $content = \Form::open(['method' => 'DELETE', 'route' => ['employee-infos.destroy', $model->id]]);

                if (Auth::user()->can('Edit Employee Info')) {
                    $content .= '<a title="' . e(__("Edit")) . '" href="' . action("Fsm\EmployeeInfoController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }

                if (Auth::user()->can('View Employee Info')) {
                    $content .= '<a title="' . e(__("Detail")) . '" href="' . action("Fsm\EmployeeInfoController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                if (Auth::user()->can('View Employee Info History')) {
                    $content .= '<a title="' . e(__("History")) . '" href="' . action("Fsm\EmployeeInfoController@history", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-history"></i></a> ';
                }

                if (Auth::user()->can('Delete Employee Info')) {
                    $content .= '<a title="' . e(__("Delete")) . '"  class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->editColumn('status',function ($model){
                 return EmployeeStatus::getDescription($model->status);
            })
            
            ->editColumn('service_provider_id',function ($model){
                return $model->serviceProvider->company_name;
           })
            ->make(true);
    }
    /**
     * Store or update a newly created resource in storage.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function storeOrUpdate($id = null,$data)
    {
        if(is_null($id)){
            $employeeInfos = new EmployeeInfo();
            $employeeInfos->service_provider_id = $data['service_provider_id'] ? str_replace(['[',']'], '', $data['service_provider_id']) : null;
            $employeeInfos->name = $data['name'] ? $data['name'] : null;
            $employeeInfos->gender = $data['gender'] ? $data['gender'] : null;
            $employeeInfos->contact_number = $data['contact_number'] ? $data['contact_number'] : null;
            $employeeInfos->dob = $data['dob'] ? $data['dob'] : null;
            $employeeInfos->address = $data['address'] ? $data['address'] : null;
            $employeeInfos->employee_type = $data['employee_type'] ? $data['employee_type'] : null;
            $employeeInfos->year_of_experience = $data['year_of_experience'] ? $data['year_of_experience'] : null;
            $employeeInfos->wage = $data['wage'] ? $data['wage'] : null;
            $employeeInfos->license_number = $data['license_number'] ? $data['license_number'] : null;
            $employeeInfos->license_issue_date = $data['license_issue_date'] ? $data['license_issue_date'] : null;
            $employeeInfos->training_status = $data['training_status'] ? $data['training_status'] : null;
            $employeeInfos->employment_start = $data['employment_start'] ? $data['employment_start'] : null;
            $employeeInfos->status = $data['status'] ? $data['status'] : 0;
            $employeeInfos->employment_end = $data['employment_end'] ? $data['employment_end'] : null;
           

            return $employeeInfos->save();
        }
        else{
            $employeeInfos = EmployeeInfo::find($id);
            $employeeInfos->name = $data['name'] ? $data['name'] : null;
            $employeeInfos->gender = $data['gender'] ? $data['gender'] : null;
            $employeeInfos->contact_number = $data['contact_number'] ? $data['contact_number'] : null;
            $employeeInfos->dob = $data['dob'] ? $data['dob'] : null;
            $employeeInfos->address = $data['address'] ? $data['address'] : null;
            $employeeInfos->employee_type = $data['employee_type'] ? $data['employee_type'] : null;
            $employeeInfos->year_of_experience = $data['year_of_experience'] ? $data['year_of_experience'] : null;
            $employeeInfos->wage = $data['wage'] ? $data['wage'] : null;
            $employeeInfos->license_number = $data['license_number'] ? $data['license_number'] : null;
            $employeeInfos->license_issue_date = $data['license_issue_date'] ? $data['license_issue_date'] : null;
            $employeeInfos->training_status = $data['training_status'] ? $data['training_status'] : null;
            $employeeInfos->employment_start = $data['employment_start'] ? $data['employment_start'] : null;
            $employeeInfos->status = $data['status'] ? $data['status'] : 0;
            $employeeInfos->employment_end = $data['employment_end'] ? $data['employment_end'] : null;
       

            return $employeeInfos->save();
        }
    }

    /**
     * Download a listing of the specified resource from storage.
     *
     * @param array $data
     * @return null
     */
    public function download($data)
    {
        $searchData = $data['searchData'] ? $data['searchData'] : null;

        $id = $data['id'] ? $data['id'] : null;
        $employee_name = $data['employee_name'] ? $data['employee_name'] : null;
        $employee_type = $data['employee_type'] ? $data['employee_type'] : null;
        $service_provider_id = $data['service_provider_id'] ? $data['service_provider_id'] : null;
        $status = $data['status'] ? $data['status'] :null;
        $columns = ['Service Provider Name', 'Employee Name', 'Employee Gender', 'Employee Contact Number', 'Date of Birth',
             'Address', 'Desigination', 'Working Experince (Years)', 'Monthly Remuneration', 'Driving License Number', 'License Issue Date','Training Received', 'Status', 'Job Start Date ','Job End Date'];


        $query =  EmployeeInfo::select('*')->whereNull('deleted_at');

        if(Auth::user()->hasRole('Service Provider - Admin') )
        {
        $query->where('service_provider_id',"=",Auth::user()->service_provider_id);
        }

        if(!empty($id)){
            $query->where('fsm.employees.id',$id);
        }

        if(!empty($employee_name)){
            $query->where('fsm.employees.name','ILIKE','%'.$employee_name.'%');
        }

        if(!empty($employee_type)){
            $query->where('employee_type' ,$employee_type);
        }
        if(!empty($service_provider_id)){
            $query->where('fsm.employees.service_provider_id',$service_provider_id);
        }

        if(!empty($status)){
            $query -> where('fsm.employees.status', $status);
        }
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);

        $writer->openToBrowser('Employee Information.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel

        $query->chunk(5000, function ($employeeInfo) use ($writer) {
           foreach($employeeInfo as $employee) {
               if($employee->service_provider_id){
                   $serviceProvider = \App\Models\Fsm\ServiceProvider::withTrashed()->findOrFail($employee->service_provider_id);
                   $serviceProviderName = $serviceProvider->company_name;
               }
               else{
                   $serviceProviderName = null;
               }
                $values = [];
                $values[] = $serviceProviderName;
                $values[] = $employee->name;
                $values[] = $employee->gender;
                $values[] = $employee->contact_number;
                $values[] = $employee->dob;
                $values[] = $employee->address;
                $values[] = $employee->employee_type;
                $values[] = $employee->year_of_experience;
                $values[] = $employee->wage;
                $values[] = $employee->license_number;
                $values[] = $employee->license_issue_date;
                $values[] = $employee->training_status;
                $values[] = EmployeeStatus::getDescription($employee->status);
                $values[] = $employee->employment_start;
                
                $values[] = $employee->employment_end;
                
                $writer->addRow($values);
            }
        });

        $writer->close();

    }

}
