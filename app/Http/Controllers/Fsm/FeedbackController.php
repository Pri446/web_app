<?php
// Last Modified Date: 18-04-2024
// Developed By: Innovative Solution Pvt. Ltd. (ISPL)  
namespace App\Http\Controllers\Fsm;

use App\Http\Controllers\Controller;

use App\Models\Fsm\Feedback;
use App\Models\Fsm\Application;
use App\Services\Fsm\OperationalDataAccessService;
use App\Models\User;
use Auth;
use App\Http\Requests\Fsm\FeedbackRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Laracasts\Flash\Flash;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use App\Models\LayerInfo\Ward;
use DB;
use DataTables;

class FeedbackController extends Controller{

    /**
    * Create a new controller instance.
    *
    * This constructor sets up middleware for access control based on permissions for various actions related to feedbacks.
    */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:List Feedbacks', ['only' => ['index', 'getData']]);
        $this->middleware('permission:View Feedback', ['only' => ['show']]);
        $this->middleware('permission:Add Feedback', ['only' => ['createFeedback', 'store']]);
        $this->middleware('permission:Edit Feedback', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Feedback', ['only' => ['destroy']]);
        $this->middleware('permission:Export Feedbacks', ['only' => ['export']]);
    }
    /**
    * Display the index page for feedbacks.
    *
    * @param Request $request The HTTP request object.
    * @return \Illuminate\View\View The view for the feedbacks index page.
    */
    public function index(Request $request)
    {
        $page_title =__("Feedbacks");
        $wards = Ward::orderBy('ward', 'asc')->pluck('ward', 'ward')->all();
        $feedbackYears = $this->getAllFeedbacksYearsDate();
        $months = DB::select("select distinct extract(month from created_at) as date1 from fsm.feedbacks where deleted_at is null order by date1 asc");
        return view('fsm.feedbacks.index', compact('page_title', 'wards', 'months','feedbackYears'));
    }
    /**
    * Get data for the feedbacks table.
    *
    * @param Request $request The HTTP request object.
    * @return \Illuminate\Http\JsonResponse The JSON response containing the feedbacks data.
    */
    public function getData(Request $request)
    {
        $feedbacksData = DB::table('fsm.feedbacks AS f')
            ->leftJoin('auth.users AS u', 'f.user_id', '=', 'u.id')
            ->join('fsm.applications AS a', 'f.application_id', '=', 'a.id')
            ->leftJoin('fsm.emptyings AS e', function ($join) {
                $join->on('e.application_id', '=', 'a.id')
                    ->whereNull('e.deleted_at');
            })
            ->select('f.created_at','f.id','f.application_id', 'u.username', 'a.ward')
            ->whereNull('f.deleted_at');

        if (Auth::user()->hasRole('Service Provider - Help Desk')) {
            $feedbacksData->where('a.service_provider_id', Auth::user()->service_provider_id ?? -1);
        } else {
            OperationalDataAccessService::scopeQuery(
                $feedbacksData,
                'a.service_provider_id',
                'e.treatment_plant_id'
            );
        }

        return Datatables::of($feedbacksData)
            ->filter(function ($query) use ($request) {
                
                if ($request->application_id) {
                    $query->where('f.application_id', $request->application_id);
                }
                if ($request->ward) {
                    $query->where('a.ward', $request->ward);
                }
                if ($request->year) {
                    $query->whereRaw('EXTRACT(YEAR FROM f.created_at) = ?', [$request->year]);
                }
                if ($request->month) {
                    $query->whereRaw('EXTRACT(MONTH FROM f.created_at) = ?', [$request->month]);
                }
                if ($request->day) {
                    $query->whereRaw('EXTRACT(DAY FROM f.created_at) = ?', [$request->day]);
                }
                if ($request->application_id) {
                    $query->where('f.application_id', $request->application_id);
                }
                if ($request->date_from && $request->date_to) {
                    $query->whereBetween('f.created_at', [Date::parse($request->date_from), Date::parse($request->date_to)]);
                }
            })
            ->addColumn('action', function ($model) {
                $application = Application::find($model->application_id);
                
                $content = \Form::open(['method' => 'DELETE', 'route' => ['feedback.destroy', $model->id]]);

                if (Auth::user()->can('View Feedback')) {
                    $content .= '<a title="' . __("Detail") . '" href="' . action("Fsm\FeedbackController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }
                if (Auth::user()->can('Delete Feedback')) {
                    $content .= '<a title="' . __("Delete") . '" class="delete btn btn-danger btn-sm mb-1"><i class="fa fa-trash"></i></a> ';
                }

                $content .= \Form::close();
                return $content;
            })
            ->make(true);
    }
    
    /**
    * Get the minimum and maximum years from the 'created_at' field of the 'fsm.feedbacks' table.
    *
    * @param string|null $ward The ward to filter feedbacks for (optional).
    * @return \stdClass The object containing the minimum and maximum years.
    */
    private function getAllFeedbacksYearsDate($ward = null)
    {
        $query = "SELECT MIN(EXTRACT(YEAR FROM created_at)) AS miny, MAX(EXTRACT(YEAR FROM created_at)) AS maxy FROM fsm.feedbacks";
        $results = DB::select($query);
        return $results[0];
    }
    
    /**
    * Display the specified feedback.
    *
    * @param  int  $id The ID of the feedback.
    * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory The view to display the feedback details.
    */
    public function show($id)
    {
        $feedback = Feedback::findOrFail($id);
        $this->authorizeFeedbackRecord($feedback);

        $page_title = __("Feedback Details");
        return view('fsm.feedbacks.show', compact('page_title', 'feedback'));
    }
    
    /**
    * Display a form for creating a new feedback for the specified application.
    *
    * @param  int  $id The ID of the application.
    * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory The view to create a new feedback.
    */
    public function createFeedback($id)
    {
        $application = Application::findOrFail($id);
        OperationalDataAccessService::authorizeApplication($application);

        if ($application) {
            $feedback = new Feedback;
            $feedback->application_id = $id;
            $page_title = __("Add Feedback Details");
            return view('fsm.feedbacks.create', compact('page_title', 'feedback', 'application'));

        } else {
            abort(404);
        }
    }
    
    /**
    * Display a form for editing the specified feedback.
    *
    * @param  int  $id The ID of the feedback.
    * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory The view to edit the feedback details.
    */
    public function edit($id)
    {
        $feedback = Feedback::findOrFail($id);
        $this->authorizeFeedbackRecord($feedback);
        if(Auth::user()->hasRole('Municipality - Help Desk') || Auth::user()->hasRole('Service Provider - Help Desk')) {
            if($feedback->user_id != Auth::user()->id) {
                return redirect('fsm/application')->with('error',__('Cannot update Feedback not created by current User.'));
            }
        }
        if( !(Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Municipality - Sanitation Department')) )
        {
            if($feedback->created_at->diffInDays(today()) > 1)
            {
                return redirect('fsm/application')->with('error',__('Cannot update Feedback Information 24 hours after creation. Please contact Sanitation Department for support.'));
            }
        }
        $application = Application::find($feedback->application_id);
        if ($feedback) {
            $page_title = __("Edit Feedback Details");
            return view('fsm.feedbacks.edit', compact('page_title', 'feedback', 'application'));
        } else {
            abort(404);
        }
    }
    
    /**
    * Update the specified feedback in storage.
    *
    * @param  \App\Http\Requests\FeedbackRequest  $request The request object containing the feedback data.
    * @param  int  $id The ID of the feedback to update.
    * @return \Illuminate\Http\RedirectResponse A redirect response after updating the feedback.
    */
    public function update(FeedbackRequest $request, $id)
    {

        $feedback = Feedback::findOrFail($id);
        $this->authorizeFeedbackRecord($feedback);
        $application = Application::findOrFail($feedback->application_id);

        $targetApplication = Application::findOrFail($request->application_id);
        OperationalDataAccessService::authorizeApplication($targetApplication);
        $feedback->application_id = $request->application_id? $request->application_id : null;
        $feedback->customer_name = $request->customer_name ? $request->customer_name : null;
        $feedback->customer_gender = $request->customer_gender ? $request->customer_gender : null;
        $feedback->customer_number = $request->customer_number ? $request->customer_number : null;
        $feedback->fsm_service_quality = (bool)$request->fsm_service_quality ;
        $feedback->wear_ppe = (bool)$request->wear_ppe;
        $feedback->comments = $request->comments ? $request->comments : null;
        $feedback->service_provider_id = $application->service_provider_id;

        $feedback->save();
        
        $application->feedback_status = TRUE;
        $application->save();
            return redirect('fsm/application')->with('success',__('Feedback Details updated successfully.'));

    }
    /**
    * Store a newly created feedback in storage.
    *
    * @param  \App\Http\Requests\FeedbackRequest  $request The request object containing the feedback data.
    * @return \Illuminate\Http\RedirectResponse A redirect response after storing the feedback.
    */
    public function store(FeedbackRequest $request)
    {
        $application = Application::findOrFail($request->application_id);
        OperationalDataAccessService::authorizeApplication($application);
        // Check if feedback for the application already exists
        if (Feedback::where('application_id', $request->application_id)->exists() && $application->feedback_status) {
            return redirect('fsm/application')->with('error', __('Feedback for this application already exists.'));
        }
        $feedback = new Feedback;
        $feedback->application_id = $request->application_id? $request->application_id : null;
        $feedback->customer_name = $request->customer_name ? $request->customer_name : null;
        $feedback->customer_gender = $request->customer_gender ? $request->customer_gender : null;
        $feedback->customer_number = $request->customer_number ? $request->customer_number : null;
        $feedback->fsm_service_quality = (bool)$request->fsm_service_quality ;
        $feedback->wear_ppe = (bool)$request->wear_ppe;
        $feedback->comments = $request->comments ? $request->comments : null;
        $feedback->service_provider_id = $application->service_provider_id;
        $feedback->user_id = Auth::id();
        $user = User::find(Auth::id());
        $feedback->save();
        $application->feedback_status = TRUE;
        $application->save();
            return redirect('fsm/application')->with('success',__('Feedback Details Created Successfully.'));

    }
    
    /**
    * Remove the specified feedback from storage.
    *
    * @param  int  $id The ID of the feedback to be deleted.
    * @return \Illuminate\Http\RedirectResponse A redirect response after deleting the feedback.
    */
    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $this->authorizeFeedbackRecord($feedback);
        $application_id = $feedback->application_id;
        if ($feedback) {
            if(Auth::user()->hasRole('Municipality - Help Desk') || Auth::user()->hasRole('Service Provider - Help Desk')) {
                if($feedback->user_id != Auth::user()->id) {
                    return redirect('fsm/sludge-collection')->with('error',__('Cannot delete Feedback not created by current User.'));
                }
            }
            if( !(Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Municipality - Sanitation Department')) )
            {
                if($feedback->created_at->diffInDays(today()) > 1)
                {
                    return redirect('fsm/sludge-collection')->with('error',__('Cannot delete Feedback Information 24 hours after creation. Please contact Sanitation Department for support.'));
                }
            }
            // updating applicaiton->sludge_collection_status
            $application = Application::findOrFail($feedback->application_id);
            $application->feedback_status=false;
            $application->save();
            $feedback->delete();
            return redirect('fsm/feedback')->with('success',__('Feedback deleted successfully.'));
        } else {
            return redirect('fsm/feedback')->with('error',__('Failed to delete feedback.'));
        }
    }
    
    /**
    * Export feedback data to a CSV file.
    *
    * @return void
    */
    public function export()
    {
        $application_id = $_GET['application_id'] ?? null;
        $ward = $_GET['ward'] ?? null;
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        $columns = [
            __('Application ID'),
            __('Applicant Name'),
            __('Applicant Gender'),
            __('Applicant Contact Number'),
            __('Are you satisfied with the Service Quality?'),
            __('Did the sanitation workers wear PPE during desludging?'),
            __('Comments')
        ];
        
        $query = DB::table('fsm.feedbacks AS f')
            ->join('auth.users AS u', 'f.user_id', '=', 'u.id')
            ->join('fsm.applications AS a', 'f.application_id', '=', 'a.id')
            ->leftJoin('fsm.emptyings AS e', function ($join) {
                $join->on('e.application_id', '=', 'a.id')
                    ->whereNull('e.deleted_at');
            })
            ->select('f.*')
            ->whereNull('f.deleted_at')
            ->orderBy('f.id', 'asc');

        if (Auth::user()->hasRole('Service Provider - Help Desk')) {
            $query->where('a.service_provider_id','=',Auth::user()->service_provider_id);
        } else {
            OperationalDataAccessService::scopeQuery(
                $query,
                'a.service_provider_id',
                'e.treatment_plant_id'
            );
        }
        if ($application_id) {
            $query->where('f.application_id', $application_id);
        }
        if ($ward) {
            $query->where('a.ward', $ward);
        }
        if ($date_from && $date_to) {
            $query->whereBetween('f.created_at', [date('Y/M/d H:i:s', strtotime($date_from)), date('Y/M/d H:i:s', strtotime($date_to))]);
        }

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Feedbacks.csv')
            ->addRowWithStyle($columns, $style); //Top row of excel
         
        $query->chunk(5000, function ($applications) use ($writer) {
            foreach($applications as $application) {
                $values = [];
                $values[] = $application->application_id;
                $values[] = $application->customer_name;
                $values[] = $application->customer_gender;
                $values[] = $application->customer_number;
                $values[] = $application->fsm_service_quality?"Yes":"No";
                $values[] = $application->wear_ppe?"Yes":"No";
                $values[] = $application->comments;
                $writer->addRow($values);
            }
        });

        $writer->close();
    }

    /** Apply the agreed SP/TP segregation to a direct Feedback ID. */
    private function authorizeFeedbackRecord(Feedback $feedback): void
    {
        $application = Application::findOrFail($feedback->application_id);
        OperationalDataAccessService::authorizeApplication($application);
    }





}
