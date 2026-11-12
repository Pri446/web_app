<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;
use App\Models\Fsm\ServiceProvider;
use App\Http\Requests\Fsm\ServiceProviderRequest;
use Auth;
use Illuminate\Http\Request;
use App\Models\Fsm\TreatmentPlant;
use App\Models\Fsm\SludgeCollection;
use App\Models\LayerInfo\Ward;
use DB;
use App\Services\Fsm\ServiceProviderService;
use App\Services\Auth\UserService;
use App\Enums\ServiceProviderStatus;
use Illuminate\Support\Facades\Storage;

class ServiceProviderController extends Controller
{
    protected ServiceProviderService $serviceProviderService;
    protected UserService $userService;
    public function __construct(ServiceProviderService $serviceProviderService, UserService $userService)
    {
        $this->middleware('auth');
        $this->middleware('permission:List Service Providers', ['only' => ['index']]);
        $this->middleware('permission:View Service Provider', ['only' => ['show']]);
        $this->middleware('permission:Add Service Provider', ['only' => ['create', 'store']]);
        $this->middleware('permission:Edit Service Provider', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Service Provider', ['only' => ['destroy']]);
        $this->middleware('permission:Export Service Providers to CSV', ['only' => ['export']]);
        $this->serviceProviderService = $serviceProviderService;
        $this->userService = $userService;

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title =__("Service Providers") ;
        if(Auth::user()->hasRole('sludge_collection_user')) {
            $treatmentPlants = TreatmentPlant::Operational()->where('id', Auth::user()->treatment_plant_id)->orderBy('id')->pluck('name', 'id');
        }
        else {
            $treatmentPlants = TreatmentPlant::Operational()->orderBy('id')->pluck('name', 'id');
        }
        $ward = Ward::orderBy('ward')->pluck('ward','ward');
        $serviceProviderStatus = ServiceProviderStatus::asSelectArray();
        return view('fsm/service-providers.index', compact('page_title', 'treatmentPlants', 'ward', 'serviceProviderStatus'));
    }

    public function getData(Request $request)
    {
        $data = $request->all();
        return $this->serviceProviderService->getAllServiceProviders($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $page_title =__("Add Service Provider") ;
        $wards = Ward::orderBy('ward')->pluck('ward', 'ward')->toArray();
        $uncoveredWards = $this->serviceProviderService
            ->getUncoveredServiceAreaWards();
        $serviceProvider = null;
        $serviceProviderStatus = ServiceProviderStatus::asSelectArray();
        return view('fsm/service-providers.create', compact(
            'page_title',
            'wards',
            'uncoveredWards',
            'serviceProvider',
            'serviceProviderStatus'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ServiceProviderRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('contract_document_pdf')) {
            $file = $request->file('contract_document_pdf');

            $companyName = preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $request->company_name
            );

            $filename = $companyName . '_' . time() . '.pdf';

            $data['contract_document_pdf'] = $file->storeAs(
                'contract_documents',
                $filename,
                'public'
            );
        }

        $serviceProviderId = $this->serviceProviderService
            ->storeOrUpdate(null, $data);

        if ($request->filled('create_user')) {
            /*
            * validated() excludes create-user fields unless they have validation
            * rules, so copy the necessary request values.
            */
            $userData = $request->all();

            $userData['service_provider_id'] = $serviceProviderId;
            $userData['user_type'] = 'Service Provider';
            $userData['roles'] = 'Service Provider - Admin';
            $userData['gender'] = $request->contact_gender;
            $userData['username'] = explode('@', $request->email)[0];
            $userData['name'] = $request->company_name;

            $this->userService->storeOrUpdate(null, $userData);

            $successMessage = __(
                'Service Provider and Service Provider - Admin User created successfully.'
            );
        } else {
            $successMessage = __('Service Provider created successfully.');
        }

        return redirect('fsm/service-providers')
            ->with('success', $successMessage);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $serviceProvider = ServiceProvider::find($id);
        if ($serviceProvider) {
            $page_title =__("Service Provider Details") ;
            $status = ServiceProviderStatus::getDescription($serviceProvider->status);
            return view('fsm/service-providers.show', compact('page_title', 'serviceProvider', 'status'));
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
        $serviceProvider = ServiceProvider::findOrFail($id);

        $wards = Ward::orderBy('ward')
            ->pluck('ward', 'ward')
            ->toArray();

        $serviceProviderStatus =
            ServiceProviderStatus::asSelectArray();

        $selectedWards = [];

        if (!empty($serviceProvider->service_area)) {
            $selectedWards = array_map(
                'intval',
                array_filter(
                    explode(',', $serviceProvider->service_area)
                )
            );
        }

        $page_title = __('Edit Service Provider');

        return view(
            'fsm/service-providers.edit',
            compact(
                'page_title',
                'serviceProvider',
                'wards',
                'serviceProviderStatus',
                'selectedWards'
            )
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ServiceProviderRequest $request, $id)
    {
        $serviceProvider = ServiceProvider::findOrFail($id);
        $data = $request->validated();
        $wasOperational = (bool) $serviceProvider->status;
        $willBeOperational = (bool) $data['status'];
        $oldDocumentPath = $serviceProvider->contract_document_pdf;
        $newDocumentPath = null;

        if (
            $wasOperational
            && !$willBeOperational
            && $this->serviceProviderService->hasActiveApplications($serviceProvider->id)
        ) {
            return redirect('fsm/service-providers')->with(
                'error',
                __('The Service Provider cannot be made inactive while it has active or ongoing applications.')
            );
        }

        if ($request->hasFile('contract_document_pdf')) {
            $file = $request->file('contract_document_pdf');

            $companyName = preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $request->company_name
            );

            $filename = $companyName . '_' . time() . '.pdf';

            $newDocumentPath = $file->storeAs(
                'contract_documents',
                $filename,
                'public'
            );
            $data['contract_document_pdf'] = $newDocumentPath;
        } else {
            $data['contract_document_pdf'] =
                $serviceProvider->contract_document_pdf;
        }

        try {
            $this->serviceProviderService->storeOrUpdate(
                $serviceProvider->id,
                $data
            );
        } catch (\DomainException $exception) {
            if ($newDocumentPath) {
                Storage::disk('public')->delete($newDocumentPath);
            }

            return redirect('fsm/service-providers')
                ->with('error', $exception->getMessage());
        }

        if (
            $newDocumentPath
            && $oldDocumentPath
            && Storage::disk('public')->exists($oldDocumentPath)
        ) {
            Storage::disk('public')->delete($oldDocumentPath);
        }

        $redirect = redirect('fsm/service-providers')->with(
            'success',
            __('Service Provider updated successfully.')
        );

        if (!$wasOperational && $willBeOperational) {
            $redirect->with('activation_review', true);
        }

        return $redirect;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $serviceProvider = ServiceProvider::find($id);
        if ($serviceProvider) {

            if($serviceProvider->applications()->exists())
            {
                $applicationsCount =  $serviceProvider->applications()->count();
                if($applicationsCount > 0)
                    {
                        return redirect('fsm/service-providers')->with('error',__('Cannot delete Service Provider that has associated Application Information. Please make the provider inactive instead.'));
                    }
            }
            if($serviceProvider->users()->exists()){
                return redirect('fsm/service-providers')->with('error',__('Cannot delete Service Provider that has associated User Information. Please make the provider inactive instead.'));
            }
            if($serviceProvider->vacutugTypes()->exists()){
                return redirect('fsm/service-providers')->with('error',__('Cannot delete Service Provider that has associated Desludging Vehicle Information. Please make the provider inactive instead.'));
            }
            if($serviceProvider->employees()->exists()){
                return redirect('fsm/service-providers')->with('error',__('Cannot delete Service Provider that has associated Employee Information. Please make the provider inactive instead.'));
            }
            $serviceProvider->delete();
            
            return redirect('fsm/service-providers')->with('success',__('Service Provider deleted successfully.'));
            
        } else 
        {
            return redirect('fsm/service-providers')->with('error',__('Failed to delete service provider.'));
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
        $serviceProvider = ServiceProvider::find($id);
        if ($serviceProvider) {
            $page_title =__("Service Provider History") ;
            return view('fsm/service-providers.history', compact('page_title', 'serviceProvider'));
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
        return $this->serviceProviderService->download($data);
        
    }
}
