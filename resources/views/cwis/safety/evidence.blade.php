@php($pathColumn = $kind === 'biosolids' ? 'biosolids_document_path' : 'document_path')
@php($nameColumn = $kind === 'biosolids' ? 'biosolids_document_name' : 'document_name')
@if($evidenceModel && $evidenceModel->$pathColumn)
<div class="mb-2" style="overflow-wrap:anywhere;">
    <a href="{{ route('cwis.safety.document',['submission'=>$submission->id,'document'=>$evidenceModel->id,'kind'=>$kind,'preview'=>1]) }}" target="_blank" rel="noopener">{{ $evidenceModel->$nameColumn }}</a>
    <a class="ml-2" href="{{ route('cwis.safety.document',['submission'=>$submission->id,'document'=>$evidenceModel->id,'kind'=>$kind]) }}" title="{{ __('Download Evidence') }}" aria-label="{{ __('Download Evidence') }}"><i class="fa fa-download" aria-hidden="true"></i></a>
</div>
@endif
