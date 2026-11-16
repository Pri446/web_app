<?php
// Last Modified Date: 18-04-2024
//Developed By: Innovative Solution Pvt. Ltd. (ISPL)  (© ISPL, 2024)
namespace App\Http\Controllers\TaxPaymentInfo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaxPaymentInfo\TaxPaymentStatus;
use App\Models\LayerInfo\Ward;
use App\Models\TaxPaymentInfo\DueYear;
use DataTables;
use Illuminate\Support\Facades\DB as DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\AbstractWriter;
use App\Models\TaxPaymentInfo\TaxPayment;
use App\Services\OwnerPiiAuditService;
use App\Services\PropertyTaxPiiAccessService;
use App\Services\PropertyTaxPiiPresenter;
use Illuminate\Support\Facades\Auth;

class TaxPaymentController extends Controller
{
    private PropertyTaxPiiAccessService $piiAccess;
    private PropertyTaxPiiPresenter $piiPresenter;
    private OwnerPiiAuditService $piiAudit;

    public function __construct(
        PropertyTaxPiiAccessService $piiAccess,
        PropertyTaxPiiPresenter $piiPresenter,
        OwnerPiiAuditService $piiAudit
    )
    {
        $this->piiAccess = $piiAccess;
        $this->piiPresenter = $piiPresenter;
        $this->piiAudit = $piiAudit;
        $this->middleware('auth');
        $this->middleware('permission:List Property Tax Collection', ['only' => ['index']]);
        $this->middleware('permission:Import Property Tax Collection From CSV', ['only' => ['create', 'store']]);
        $this->middleware('permission:Export Property Tax Collection Info', ['only' => ['export', 'exportunmatched']]);
        $this->middleware('pii.no-cache', ['only' => ['getData']]);

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = __("Property Tax Collection");
        $wards = Ward::getInAscOrder();
        $dueYears = DueYear::getInAscOrder();
        $user = Auth::user();
        $propertyTaxPiiUnlocked = $user && $this->piiAccess->isUnlocked($user);
        $canUnlockPropertyTaxPii = $this->piiAccess->canUnlock($user);
        $canExportPropertyTaxPii = $user
            && $user->can('List Property Tax Collection')
            && $user->can('View Property Tax Owner PII')
            && $user->can('Unlock Property Tax Owner PII')
            && $user->can('Export Property Tax Owner PII');
        // Full-dataset export is intentionally narrower than selected-BIN
        // export because Property Tax records have no municipality row scope.
        $canExportAllPropertyTaxPii = $canExportPropertyTaxPii
            && $user->hasAnyRole([
                'Super Admin',
                'Municipality - Super Admin',
                'Municipality - IT Admin',
            ]);
        $propertyTaxPiiUnlockSeconds = $propertyTaxPiiUnlocked
            ? $this->piiAccess->secondsRemaining($user)
            : 0;

        return view('taxpayment-info.index', compact(
            'page_title',
            'wards',
            'dueYears',
            'propertyTaxPiiUnlocked',
            'canUnlockPropertyTaxPii',
            'canExportPropertyTaxPii',
            'canExportAllPropertyTaxPii',
            'propertyTaxPiiUnlockSeconds'
        ));
    }
    /**
     * Prepare data for the DataTable.
     *
     * @param Request $request
     * @return DataTables
     * @throws Exception
     */
    public function getData(Request $request)
    {
        $user = Auth::user();
        $revealed = $user && $this->piiAccess->isUnlocked($user);

        if ($revealed) {
            $this->piiAudit->record(
                'property_tax_owner_pii_list_viewed',
                true,
                $user,
                null,
                [
                    'surface' => 'property_tax_list',
                    'page_start' => (int) $request->input('start', 0),
                    'page_length' => (int) $request->input('length', 10),
                ],
                'password_reentry'
            );
        }

        // Select only the fields used by the table. Owner contact must never
        // be included in this JSON response.
        $buildingData = DB::table(DB::raw('(SELECT status.tax_code, status.bin, status.ward,
                payment.owner_name, status.due_year
            FROM taxpayment_info.tax_payment_status status
            LEFT JOIN taxpayment_info.tax_payments payment
                ON payment.tax_code = status.tax_code
            UNION ALL
            SELECT payment.tax_code, NULL AS bin, NULL AS ward,
                payment.owner_name, NULL AS due_year
            FROM taxpayment_info.tax_payments payment
            WHERE NOT EXISTS (
                SELECT 1 FROM building_info.buildings building
                WHERE building.tax_code = payment.tax_code
                    AND building.deleted_at IS NULL
            )) tax'))
            ->leftjoin('taxpayment_info.due_years AS due', 'due.value', '=', 'tax.due_year')
            ->select([
                'tax.tax_code',
                'tax.bin',
                'tax.ward',
                'tax.owner_name',
                'due.name',
            ]);

        return DataTables::of($buildingData)
            ->filter(function ($query) use ($request) {
                if ($request->dueyear_select) {
                    $query->where('due.name', $request->dueyear_select);
                }
                if ($request->ward_select) {
                    $query->where('tax.ward', $request->ward_select);
                }
                if ($request->tax_code) {
                    $query->where('tax_code', 'ILIKE', '%' . $request->tax_code . '%');
                }
                if ($request->bin) {
                    $query->where('tax.bin', 'ILIKE', '%' . $request->bin . '%');
                }
            })
            ->editColumn('owner_name', function ($row) use ($revealed) {
                return $this->piiPresenter->presentValue(
                    $row->owner_name,
                    $revealed
                );
            })
            ->editColumn('bin', function ($row) {
                return $row->bin ?: __('Unmatched');
            })
            ->make(true);


    }
    /**
     * Display the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $page_title = __("Import Property Tax Collection Information Support System");
        return view('taxpayment-info.create', compact('page_title'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        Validator::extend('file_extension', function ($attribute, $value, $parameters, $validator) {
                if( !in_array( $value->getClientOriginalExtension(), $parameters ) ){
                return false;
            }
            else {
                return true;
            }
        }, __('File must be csv format.') );
        $this->validate($request,
                ['csvfile' => 'required|file_extension:csv'],
                ['required' => __('The csv file is required.')],
        );

        if (!$request->hasfile('csvfile')) {

            return redirect('tax-payment/data')->with('error',__('The csv file is required.'));
        }
        if ($request->hasFile('csvfile')) {

                $filename = 'building-tax-payments.' . $request->file('csvfile')->getClientOriginalExtension();
                try {
                    $stored = $request->file('csvfile')->storeAs('', $filename, 'importtax');
                } catch (\Throwable $e) {
                    \Log::error('Property Tax upload storage failed.', ['exception_type' => get_class($e)]);
                    return back()->withErrors(['csvfile' => __('Unable to save the tax CSV. Ask the administrator to run php artisan storage:setup and make the configured tax upload directory writable by the PHP service user. Previous tax data was preserved.')]);
                }

                if ($stored)
                {
                    // storeAs() already returns the path of the exact file that
                    // was stored. Use that path directly instead of scanning the
                    // entire import directory with listContents().
                    //
                    // This is important for two reasons:
                    // 1. Flysystem v3 requires listContents($path), so the old
                    //    zero-argument call throws an ArgumentCountError.
                    // 2. Selecting the first directory entry could import an
                    //    older/unrelated file when more than one file exists.
                    //
                    // disk()->path() also returns a valid native Windows path.
                    // Do not remove backslashes: doing that turns a valid path
                    // such as E:\\app\\storage\\file.csv into an invalid one.
                    $filePath = Storage::disk('importtax')->path($stored);

                    try {
                        $result = app(\App\Services\TaxPaymentImportService::class)->import($filePath);
                    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                        // Do not flash row values, which can contain owner PII.
                        $errors = [];
                        foreach ($e->failures() as $failure) {
                            $errors[] = __('CSV row :row: invalid :field.', [
                                'row' => $failure->row(), 'field' => $failure->attribute(),
                            ]);
                            if (count($errors) >= 20) break;
                        }
                        return back()->withErrors(['csvfile' => array_merge(
                            [__('Import cancelled. Previous data was preserved.')], $errors
                        )]);
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        throw $e;
                    } catch (\Throwable $e) {
                        // Database errors may contain plaintext or encrypted owner values.
                        \Log::error('Property Tax import failed.', ['exception_type' => get_class($e)]);
                        return back()->withErrors(['csvfile' => __('Import failed. Previous data was preserved. Check the database functions and PII encryption configuration.')]);
                    }

                    $message = __('Imported :total tax records. :matched matched an active building; :unmatched are unmatched. Unmatched records need the same Tax Code/Holding ID on an existing mapped building.', $result);
                    return redirect('tax-payment')->with('success', $message);

                }
                else{
                    $message = __('Building Tax Payments Not Imported From CSV.');
                }

        }
        flash('Could not import from CSV. Try Again');
        return redirect('tax-payment');
    }
    /**
     * Export building tax payment data to a CSV file.
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $ward = $_GET['ward'] ?? null;
        $due_year = $_GET['due_year'] ?? null;
        $tax_code = $_GET['tax_code'] ?? null;
        $bin = $_GET['bin'] ?? null;

        $columns = [
            __('Tax Code'),
            __('BIN'),
            __('Ward'),
            __('Due Years')
        ];

        $query = DB::table('taxpayment_info.tax_payment_status AS tax')
                                ->leftjoin('taxpayment_info.due_years AS due', 'due.value', '=', 'tax.due_year')
                                ->select([
                                    'tax.tax_code',
                                    'tax.bin',
                                    'tax.ward',
                                    'due.name',
                                ])
                                ->where('tax.deleted_at', null)
                                ->orderBy('tax.tax_code', 'ASC');

        if (!empty($ward)) {
            $query->where('tax.ward', $ward);
        }
        if (!empty($due_year)) {
            $query->where('due.name', $due_year);
        }
        if (!empty($tax_code)) {
            $query->where('tax.tax_code', 'ILIKE', '%' . $tax_code . '%');
        }
        if (!empty($bin)) {
            $query->where('tax.bin', 'ILIKE', '%' . $bin . '%');
        }
        
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Property Tax Collection Information Support System.csv')
            ->addRowWithStyle($columns, $style); //Top row of CSV

        $query->chunk(5000, function ($taxpayments) use ($writer) {
            foreach($taxpayments as $taxpayment) {

                $values = [];
                $values[] = $taxpayment->tax_code;
                $values[] = $taxpayment->bin;
                $values[] = $taxpayment->ward;
                $values[] = $taxpayment->name;
                $writer->addRow($values);
            }
        });

        $writer->close();
    }
    public function exportunmatched()
    {
        $columns = [
            __('Tax Code'),
            __('Last Payment date')
        ];

        $query = DB::table('taxpayment_info.tax_payments AS tax')
                 ->leftjoin('building_info.buildings as b', function ($join) {
                     $join->on('tax.tax_code', '=', 'b.tax_code')->whereNull('b.deleted_at');
                 })
                 ->select([
                     'tax.tax_code',
                     'tax.last_payment_date',
                 ])
                 ->whereNull('b.tax_code')
                 ->orderBy('tax.tax_code', 'ASC');    
       
        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();

        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Unmatched Records-Property Tax Collection Information Support System.csv')
            ->addRowWithStyle($columns, $style); //Top row of CSV

        $query->chunk(5000, function ($taxpayments) use ($writer) {
            foreach($taxpayments as $taxpayment) {
                $values = [];
                $values[] = $taxpayment->tax_code;
                $values[] = $taxpayment->last_payment_date;
                $writer->addRow($values);
            }
        });

        $writer->close();
    }
}
