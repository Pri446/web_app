<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)
namespace App\Http\Controllers\Fsm;

use Spatie\Permission\Models\Permission;
use App\Http\Controllers\Controller;
use App\Models\Fsm\EmployeeInfo;
use App\Http\Requests\Fsm\EmployeeInfoRequest;
use App\Models\Fsm\ServiceProvider;
use Auth;
use Illuminate\Http\Request;
use DB;
use Validator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Fsm\EmployeeInfoService;
use App\Enums\EmployeeStatus;
use Carbon\Carbon;
use App\Models\User;

class EmployeeInfoController extends Controller
{
    protected EmployeeInfoService $employeeInfoService;

    public function __construct(EmployeeInfoService $employeeInfoService)
    {
        $this->middleware('auth');
        $this->middleware('permission:List Employee Infos', ['only' => ['index']]);
        $this->middleware('permission:View Employee Info', ['only' => ['show']]);
        $this->middleware('permission:Add Employee Info', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Employee Info', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Employee Info', ['only' => ['destroy']]);
        $this->middleware('permission:Export Employee Infos', ['only' => ['export']]);
        $this->middleware('permission:View Employee Info History', ['only' => ['history']]);

        $this->employeeInfoService = $employeeInfoService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title =__("Employee Information") ;
        $status = EmployeeStatus::asSelectArray();
        $service_provider = Auth::user()->service_provider;
        $service_provider_id = $service_provider
            ? [$service_provider->id => $service_provider->company_name]
            : [];
        $employeeInfos = null;
        return view('fsm/employee-infos.index', compact('page_title', 'status', 'service_provider_id'));
    }


    public function getData(Request $request)
    {
        $data = $request->all();
        return $this->employeeInfoService->getAllEmployeeInfo($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $page_title =__("Add Employee Information") ;
        if (Auth::user()->service_provider_id) {
            $id = Auth::user()->service_provider_id;
            $service_provider_id = $id;
            $service_providers = ServiceProvider::Operational()
                ->where('id', $id)
                ->pluck('company_name', 'id');
        } else {
            $service_provider_id = ServiceProvider::Operational()->pluck('company_name', 'id');
            $service_providers = null;
        }
        $employeeInfos = null;
        $status = EmployeeStatus::asSelectArray();
        return view('fsm/employee-infos.create', compact('page_title', 'service_provider_id', 'service_providers', 'employeeInfos', 'status'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(EmployeeInfoRequest $request)
    {
        $data = $request->validated();
        $employeeInfos = $this->employeeInfoService->storeOrUpdate($id = null, $data);

        return redirect('fsm/employee-infos')->with('success', __('Employee Information created successfully.'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $employeeInfos = EmployeeInfo::find($id);
        if ($employeeInfos) {
            $service_provider = ServiceProvider::Operational()->find($employeeInfos->service_provider_id);
            $service_provider_id = $service_provider ? $service_provider->company_name : null;
            $page_title = __("Employee Information Details");
            $status = EmployeeStatus::getDescription($employeeInfos->status);
            return view('fsm/employee-infos.show', compact('page_title', 'employeeInfos', 'status', 'service_provider_id'));
        } else {
            abort(404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $employeeInfos = EmployeeInfo::find($id);
        if ($employeeInfos) {
            $start = Carbon::parse($employeeInfos->employment_start)->format('Y-m-d'); // Use 'Y-m-d'
            $end = Carbon::parse($employeeInfos->employment_end)->format('Y-m-d'); // Use 'Y-m-d'
            $page_title = __("Edit Employee Information");
            $service_provider_id = ServiceProvider::Operational();

            if (Auth::user()->service_provider_id) {
                $service_provider_id->where('id', Auth::user()->service_provider_id);
            }

            $service_provider_id = $service_provider_id->pluck('company_name', 'id');
            $status = EmployeeStatus::asSelectArray();
            return view('fsm/employee-infos.edit', compact('page_title', 'employeeInfos', 'service_provider_id', 'status', 'start', 'end'));
        } else {
            abort(404);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(EmployeeInfoRequest $request, EmployeeInfo $employeeInfo)
    {
        $employeeInfos = EmployeeInfo::find($employeeInfo->id);

        if ($employeeInfos) {
            $data = $request->validated();

            $employeeInfos = $this->employeeInfoService->storeOrUpdate($employeeInfo->id, $data);

            return redirect('fsm/employee-infos')->with('success', __('Employee Information updated successfully.'));
        } else {
            return redirect('fsm/employee-infos')->with('error', __('Failed to update Employee Info.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $employeeInfos = EmployeeInfo::find($id);

        if ($employeeInfos) {
            if (
                $employeeInfos->driverEmptyings()->exists()
                || $employeeInfos->emptyings1()->exists()
                || $employeeInfos->emptyings2()->exists()
            ) {
                return redirect('fsm/employee-infos')->with('error', __('Cannot delete Employee Information that has associated Emptying Information. Please make the employee inactive instead.'));
            }
            $employeeInfos->delete();
            return redirect('fsm/employee-infos')->with('success', __('Employee Information deleted successfully.'));
        } else {
            return redirect('fsm/employee-infos')->with('error', __('Failed to delete Employee Information.'));
        }
    }

    /**
     * Display history of the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function history($id)
    {
        $employeeInfos = EmployeeInfo::find($id);
        if ($employeeInfos) {
            $page_title =__("Employee Information History") ;
            return view('fsm/employee-infos.history', compact('page_title', 'employeeInfos'));
        } else {
            abort(404);
        }
    }

    /**
     * Export a listing of the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        $data = $request->all();
        return $this->employeeInfoService->download($data);
    }
}
