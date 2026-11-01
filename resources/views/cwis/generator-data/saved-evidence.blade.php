@if($saved && $saved->document_path)
    <div class="mb-2" style="overflow-wrap: anywhere;">
        <a href="{{ route($documentRoute, ['submission' => $submission->id, 'document' => $saved->id, 'preview' => 1]) }}" target="_blank" rel="noopener" title="{{ __('Open Evidence') }}">
            <i class="fa fa-file-text-o" aria-hidden="true"></i> {{ $saved->document_name ?: __('Attached Evidence') }}
        </a>
        <a class="ml-2" href="{{ route($documentRoute, ['submission' => $submission->id, 'document' => $saved->id]) }}" title="{{ __('Download Evidence') }}" aria-label="{{ __('Download Evidence') }}">
            <i class="fa fa-download" aria-hidden="true"></i>
        </a>
    </div>
@endif
