<?php

namespace App\Http\Controllers\Language;

use App\Http\Controllers\Controller;
use App\Http\Requests\Language\LanguageRequest;
use Illuminate\Http\Request;
use App\Models\Language\Language;
use App\Models\Language\Translate;
use App\Models\Language\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cookie;
use DB;
use DataTables;
use Auth;
use Box\Spout\Writer\Style\Color;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Validator;
use App\Imports\TranslateImport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
class LanguageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:List Languages', ['only' => ['index']]);
        $this->middleware('permission:View Language', ['only' => ['show']]);
        $this->middleware('permission:Add Language', ['only' => ['create', 'store', 'import_translates']]);
        $this->middleware('permission:Edit Language', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete Language', ['only' => ['destroy']]);
        $this->middleware('permission:Export Language', ['only' => ['export']]);
        $this->middleware('permission:View Language', ['only' => ['history']]);
        $this->middleware('permission:Generate Translation', ['only' => ['generate_translate']]);
        $this->middleware('permission:Export CSV Template', ['only' => ['export_csv_format']]);
        $this->middleware('permission:Add Language', ['only' => ['create', 'store','import_translates']]);


    }
    /**

     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $languages = Language::all();
        $page_title = __('Languages');
        return view("language.index", compact('languages', 'page_title'));
    }



    public function generate_translate($code)
    {
        $lang = $code;
        if ($this->is_lang_exist($lang)) {
            $translate = $this->get_translation('en', false);
            $output = [];
            foreach ($translate as $base) {
                $key = $base->key;
                $get = self::get_by_key($key, $lang);
                $gen_key = ($base->load == 1) ? $key : $base->text;
                $get_txt = (!empty($get)) ? $get->text : $base->text;
                $output[$gen_key] = $get_txt;
            }
            $filewrite = false;
            try {
                File::put(lang_path('test.json'), 'Nio Testing');
                File::delete(lang_path('test.json'));
                $filewrite = true;
            } catch (\Exception $e) {
            }
            if ($filewrite) {

                $content = json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                $generate = $this->generate_lang_file($lang, $content, 'update');
                if ($generate && $generate->status == true) {
                    // $this->add_setting('lang_last_generate_' . $lang, time());
                    return redirect('language/setup')->with('success', __('Language file generated successfully'));
                } else {
                    return redirect('language/setup')->with('error', __('Failed to generate the language file.'));
                }
            } else {
                return redirect('language/setup')->with('error', __(('Unable to generate language file. Please request system administrator to check file permission of your /lang folder.')));
            }
        } else {
            return redirect('language/setup')->with('error',__('Sorry! Unable find the language'));
        }
        return $result;
    }

    public function get_translation($lang = 'base', $only = true)
    {
        $get_only = ($only == true) ? ['key', 'name', 'text', 'load'] : ['key', 'name', 'text', 'pages', 'group', 'platform', 'load'];
        return Translate::where('name', $lang)->get($get_only);
    }
    // function to return translation values by key
    public function get_by_key($key, $lang = 'base')
    {
        return Translate::where(['key' => $key, 'name' => $lang])->first();
    }
    // function to check if selected language exists in langauge table
    public function is_lang_exist($lang, $column = 'code')
    {
        $get_lang = Language::where($column, $lang)->first();
        return ($get_lang) ? true : false;
    }
    // sub function that stores the file in correct path
    public function generate_lang_file($lang, $content, $action = 'update')
    {
        $result = ['status' => false];

        if ($action === 'store') {
            $lang_file = lang_path($lang . '.json');
            if (File::exists($lang_file)) {

                File::delete($lang_file);
            }
            File::put($lang_file, $content);
        } else {

            $file_name = $lang . '.json';
            $lang_file = lang_path($file_name);
            if (File::isWritable(lang_path())) {
                if (File::exists($lang_file)) {

                    File::delete($lang_file);
                }
                File::put($lang_file, $content);
                $result = ['status' => true];
            }
        }
        return (object)$result;
    }
    // function that is called when the language is changed
    // sets app_language cookie as the selected language
    public function set_lang(Request $request)
    {
        $lang = isset($request->lang) ? $request->lang : 'en';
        $_key = Cookie::queue(Cookie::make('app_language', $lang, (60 * 24 * 365)));
        return back();
    }







    /**
     * Fetch and format data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $languages = Language::whereNull('deleted_at')->orderBy('id', 'asc');

        return Datatables::of($languages)
            ->filter(function ($query) use ($request) {})
            ->addColumn('action', function ($model) {
                if ($model->code =="en") {
                    return '';
                }

                $content = '';

                // Edit Button
                if (Auth::user()->can('Edit Language')) {
                    $content .= '<a title="'.__("Edit").'" href="' . action("Language\LanguageController@edit", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-edit"></i></a> ';
                }

                // Detail Button
                if (Auth::user()->can('View Language')) {
                    $content .= '<a title="'.__("Detail").'" href="'. action("Language\LanguageController@show", [$model->id]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-list"></i></a> ';
                }

                // Add Translations Button
                if (Auth::user()->can('Add Translation')) {

                    $content .= '<a title= "'.__("Add/Edit Translations").'" href="' . action("Language\LanguageController@add_translation", [$model->code]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-language" aria-hidden="true"></i></a> ';
                }

                // Import Translations Button
                if (Auth::user()->can('Add Translation')) {
                    $content .= '<a title="'.__("Import Translations").'" href="' . action("Language\LanguageController@create_import", [$model->code]) . '" class="btn btn-info btn-sm mb-1"><i class="fa fa-upload"></i></a> ';
                }

                // Generate Language Button (Form Submission)
                if (Auth::user()->can('Generate Translation')) {
                    $content .= \Form::open(['method' => 'POST', 'route' => ['lang.generate', $model->code], 'class' => 'd-inline']);
                    $content .= '<button type="submit" class="btn btn-info btn-sm mb-1 mr-1" title="'.__("Generate Language").'"><i class="fa fa-cogs"></i></button>';
                    $content .= \Form::close();
                }

                // Delete Button (Last)
                if (Auth::user()->can('Delete Language')) {
                    $content .= \Form::open(['method' => 'DELETE', 'route' => ['setup.destroy', $model->id], 'class' => 'd-inline']);
                    $content .= '<button type="submit" class="btn btn-danger btn-sm mb-1 delete" title="'.__("Delete").'"><i class="fa fa-trash"></i></button>';
                    $content .= \Form::close();
                }

                return $content;
            })
            ->editColumn('status', function ($model) {
                return $model->status ? 'Active' : 'Disabled';
            })
            ->make(true);
    }





    public function create()
    {
        $page_title = __("Add Language");
        return view('language.create', compact('page_title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function create_import($id)
    {
        $page_title = __('Import Translations');
        return view("language.import", compact('page_title', 'id'));
    }
    public function store(LanguageRequest $request)
    {
        // Check if there are any existing translations in the table
        $existingTranslates = Translate::where('name', 'en')->get();
        if ($existingTranslates->isEmpty()) {
            return redirect()->back()->with('error', __('The default language translations are missing. Please import them before proceeding.'));
        }

        DB::beginTransaction();
        try {
            $data = $request->all();
            $languageId = $this->storeOrUpdate(null, $data); // Create new language or restore soft-deleted one

            // Fix sequence issue (PostgreSQL-specific)
            DB::statement("SELECT setval('language.translates_id_seq', COALESCE((SELECT MAX(id) + 1 FROM language.translates), 1))");

            // Insert new translations
            foreach ($existingTranslates as $translate) {
                Translate::create([
                    'key'      => $translate->key,
                    'name'     => $data['code'] ?? null,
                    'text'     => $translate->text,
                    'pages'    => $translate->pages,
                    'group'    => $translate->group,
                    'platform' => $translate->platform,
                    'load'     => $translate->load,
                ]);
            }

            DB::commit();
            return redirect('language/setup')->with('success', __('Language added successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error(__('Error in storing Language and Translates: ') . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }


    public function storeOrUpdate($id, $data)
    {
        try {
            if (is_null($id)) {
                // Check for soft-deleted language with same code
                $trashed = Language::withTrashed()->where('code', $data['code'])->first();

                if ($trashed) {
                    if ($trashed->trashed()) {
                        $trashed->restore(); // Auto-restore
                        $trashed->name = $data['name'] ?? $trashed->name;
                        $trashed->status = $data['status'] ?? $trashed->status;
                        $trashed->save();

                        return $trashed->id;
                    } else {
                        throw new Exception(__('A language with this code already exists. Please choose a different code.'));
                    }
                }

                $language = new Language();
            } else {
                $language = Language::find($id);
                if (!$language) {
                    throw new Exception(__('Language not found'));
                }

                // Check for duplicate code (including soft-deleted)
                $trashed = Language::withTrashed()
                    ->where('code', $data['code'])
                    ->where('id', '!=', $id)
                    ->first();

                if ($trashed) {
                    throw new Exception(__('Another language with this code exists (even if deleted). Please choose a different code.'));
                }

                // Update code in translations
                Translate::where('name', $language->code)->update([
                    'name' => $data['code'] ?? null
                ]);
            }

            $language->name = $data['name'] ?? null;
            $language->code = $data['code'] ?? null;
            $language->status = $data['status'] ?? null;
            $language->save();

            return $language->id;
        } catch (Exception $e) {
            throw new Exception(__('Error in storing/updating Language and Translates: ') . $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $language = Language::find($id);
        if ($language) {
            $page_title = __("Language Details");
            return view('language.show', compact('page_title',  'language'));
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
        $page_title =__("Edit Language");
        $language = Language::find($id);
        return view('language.edit', compact('page_title', 'language'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LanguageRequest $request, $id)
    {

        $language = Language::find($id);

        if ($language) {
            $data = $request->all();
            $this->storeOrUpdate($language->id, $data);
            return redirect('language/setup')->with('success', __('Language updated successfully'));
        } else {
            return redirect('language/setup')->with('error', __('Failed to update Language'));
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
        DB::beginTransaction();
        try {
            $language = Language::find($id);
            if (!$language) {
                throw new Exception(__('Language not found'));
            }

            // Delete related Translations
            Translate::where('name', $language->code)->delete();

            // Delete the JSON language file if it exists
            $langFilePath = resource_path('lang/' . $language->code . '.json');
            if (file_exists($langFilePath)) {
                unlink($langFilePath);
            }

            // Delete Language
            $language->delete();

            DB::commit();
            return redirect()->back()->with('success', __('Language deleted successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error(__('Error in deleting Language and Translates: ') . $e->getMessage());
            return redirect()->back()->with('error', __('Something went wrong: ') . $e->getMessage());
        }
    }




    public function add_translation($languageId)
{
    $pageTitle = __('Add Translations');

    // Fetch and group source translations
    $sourceTranslations = DB::table('language.translates')
        ->where('name', 'en')
        ->select('key', 'text', 'pages','group')
        ->distinct('key')
        ->get()
        ->sortBy('pages')
        ->groupBy('pages')
        ->map(function ($group) {
            return collect($group)->sortBy('text');
        });

    // Define custom names for pages
    $customNames = [
        'Auth' => 'Authentication',
        'apiservice' => 'API Service',
        'application' => 'Application ',
        'building' => 'Buildings',
        'building_surveyor' => 'Building Surveyor Information',
        'building_dashboard' => 'Building Dashboard',
        'building_survey' => 'Building Survey',
        'containments' => 'Containment IMS',
        'cwis' => 'CWIS IMS',
        'cwis_dashboard' => 'CWIS Dashboard',
        'cwis_setting' => 'CWIS Settings',
        'dashboard' => 'Dashboard',
        'desludging_vehicles' => 'Desludging Vehicles',
        'drain_network' => 'Drain Network',
        'employee_information' => 'Employee Information',
        'emptying' => 'Emptying',
        'empting_operator' => 'Emptying Operators',
        'export_data' => 'Data Export',
        'feedbacks' => 'Feedbacks',
        'fsm_dashboard' => 'FSM Dashboard',
        'general' => 'General Information',
        'help_desks' => 'Help Desks',
        'kpi_dashboard' => 'KPI Dashboard',
        'kpi_target' => 'KPI Target',
        'landing' => 'Landing Page',
        'language' => 'Languages',
        'low_income_community' => 'Low Income Community',
        'map' => 'Map Feature',
        'performance_efficiency_standard' => 'Performance Efficiency Standards',
        'performance_efficiency_test' => 'Performance Efficiency Test',
        'property_tax_collection_iss' => 'Property Tax Collection ISS',
        'ptct_users_log' => 'PT Users Logs',
        'public_community_toilets' => 'Public / Community Toilets',
        'road_network' => 'Road Network',
        'roles' => 'Roles',
        'service_providers' => 'Service Providers',
        'sewer_page' => 'Sewer System Overview',
        'sewer_connection' => 'Sewer Connections',
        'sewage_network' => 'Sewage Network',
        'sludge_collection' => 'Sludge Collections',
        'solid_waste_iss' => 'Solid Waste ISS',
        'treatment_plants' => 'Treatment Plants',
        'users' => 'Users',
        'utility_dashboard' => 'Utility Dashboard',
        'water_samples' => 'Water Samples',
        'water_subsidy' => 'Water Subsidy Program',
        'water_supply' => 'Water Supply',
        'water_supply_network' => 'Water Supply ISS',
        'waterborne_cases_information' => 'Waterborne Cases Information',
        'waterborne_hotspot' => 'Waterborne Hotspot'
    ];


    // Get existing translations for the target language
    $existingTranslations = DB::table('language.translates')
        ->where('name', $languageId)
        ->pluck('text', 'key')
        ->toArray();

    return view('language.add_translation', compact(
        'pageTitle',
        'languageId',
        'sourceTranslations',
        'existingTranslations',
        'customNames'
    ));
}
    public function saveStepTranslation(Request $request, $languageId)
    {
        try {
            DB::beginTransaction();

            // âœ… Validate input
            $validated = $request->validate([
                'translations' => 'array',

            ]);

            $translations = $validated['translations']; // User-submitted translations

            if (empty($translations)) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('No translations provided')
                ], 400);
            }

            // Step 1: Get only the relevant existing translations from the database
            $keys = array_keys($translations); // Extract the keys from user input
            $existingTranslations = Translate::where('name', $languageId)
                ->whereIn('key', $keys)
                ->get()
                ->keyBy('key'); // Organize by 'key' for quick lookups

            $updates = [];
            $insertions = [];

            // Step 2: Loop through request data and check if updates or insertions are needed
            foreach ($translations as $key => $newText) {
                if (isset($existingTranslations[$key])) {
                    $storedText = $existingTranslations[$key]->text;

                    //  Only update if the new text is different from the stored text
                    if ($storedText !== $newText) {
                        $updates[] = [
                            'id' => $existingTranslations[$key]->id,
                            'text' => $newText
                        ];
                    }
                } else {
                    // If the translation does not exist, prepare it for insertion
                    $insertions[] = [
                        'key' => $key,
                        'name' => $languageId,
                        'text' => $newText,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            // Step 3: Perform batch updates (only for changed values)
            if (!empty($updates)) {
                foreach (array_chunk($updates, 500) as $chunk) {
                    foreach ($chunk as $update) {
                        Translate::where('id', $update['id'])->update(['text' => $update['text']]);
                    }
                }
            }

            // Step 4: Perform batch insertions (for new values)
            if (!empty($insertions)) {
                foreach (array_chunk($insertions, 500) as $chunk) {
                    Translate::insert($chunk);
                }
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => __('Translations saved successfully'),
                'updated' => count($updates),
                'inserted' => count($insertions)
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validation failed'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => __('Error saving translations'),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function getPageFromKey($key)
    {
        $parts = explode('.', $key);
        return $parts[0] ?? 'general';
    }

      // function that handles CSV import of translations
      public function import_translates($id,Request $request)
      {
          ini_set('max_execution_time', 600);
          ini_set('memory_limit', '512M');

          // CSV format and required validation
          Validator::extend('file_extension', function ($attribute, $value, $parameters, $validator) {
              // Check if the file's extension matches the allowed extensions
              return in_array($value->getClientOriginalExtension(), $parameters);
          }, __('File must be CSV format'));
          // Validate the request with custom messages
          $this->validate($request, [
              'csvfile' => 'required|file_extension:csv', // The custom file extension validation rule
          ], [
              'required' => __('The CSV file is required.'),
              'file_extension' => __('File must be CSV format'),  // Error message for the custom validation rule
          ]);

          if ($request->hasFile('csvfile')) {
              $data = Excel::toArray(new TranslateImport, $request->file('csvfile'));
              //checking csv file has all heading row keys
              $headings = (new HeadingRowImport)->toArray($request->file('csvfile'));
              $heading_row_errors = array();
              if (!in_array("key", $headings[0][0])) {
                  $heading_row_errors['key'] = __("Heading row : key is required");
              }
              if (!in_array("text", $headings[0][0])) {
                  $heading_row_errors['text'] = __("Heading row : text is required");
              }
              if (!in_array("translated_text", $headings[0][0])) {
                  $heading_row_errors['translated_text'] = __("Heading row : translated_text is required");
              }
              if (count($heading_row_errors) > 0) {
                  return back()->withErrors($heading_row_errors);
              }
              $updates = [];
              // Extract the headers from the first row
              $headers = $headings[0][0];

              // Map the columns dynamically based on the header names
              if ($data[0]) {
                  foreach ($data[0] as $row) {
                      // Extract values based on column names
                      $key = $row[array_search("key", $headers)];
                      $text = $row[array_search("text", $headers)];
                      $translatedText = $row[array_search("translated_text", $headers)];

                      // Find the translate record for the given name and key
                      $translate = Translate::where('name', $id)
                          ->where('key', $key)
                          ->first();

                      // Prepare the update data if the translate record exists and the text has changed
                      // check if translated text exists, and the imported value is not null and does not match existing stored value
                      if ($translate && !empty($translatedText) && $translatedText != $translate->text) {
                          $translate->text = $translatedText;
                          $updates[] = [
                              'id' => $translate->id,
                              'text' => $translate->text
                          ];
                      }
                  }
              }
              if (!empty($updates))
              {
                  // update values in chunks
                  foreach (array_chunk($updates, 500) as $chunk) {
                      foreach ($chunk as $update) {
                          Translate::where('id', $update['id'])->update(['text' => $update['text']]);
                      }
                  }
              }

          }
          DB::commit();
          return redirect('language/setup')->with('success', __("Translations have been imported successfully. Generate the translation file to reflect changes."));
      }

    // export csv template for import with the key values pre-filled
    public function export_csv_format()
    {
        $columns = ['key','text', 'translated_text'];

        $query = Translate::select('key', 'text')->where('name','en');

        $style = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(13)
            ->setBackgroundColor(Color::rgb(228, 228, 228))
            ->build();
        $writer = WriterFactory::create(Type::CSV);
        $writer->openToBrowser('Translation Format.csv')
            ->addRowWithStyle($columns, $style);
        $query->chunk(5000, function ($translates) use ($writer) {
            foreach ($translates as $translate) {
                $values = [];
                $values[] = $translate->key;
                $values[] = $translate->text;
                $writer->addRow($values);
            }
        });
        $writer->close();
    }

}
