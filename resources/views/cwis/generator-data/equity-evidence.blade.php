<span style="overflow-wrap:anywhere;">
    <a href="{{ route('cwis.generator-data.document', ['submission' => $submission->id, 'document' => $documentId, 'preview' => 1]) }}" target="_blank" rel="noopener">{{ $documentName ?? __('Attached Evidence') }}</a>
    <a class="ml-2" href="{{ route('cwis.generator-data.document', ['submission' => $submission->id, 'document' => $documentId]) }}" title="{{ __('Download Evidence') }}" aria-label="{{ __('Download Evidence') }}"><i class="fa fa-download" aria-hidden="true"></i></a>
</span>
