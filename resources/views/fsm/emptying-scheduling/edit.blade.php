@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
@include('layouts.components.success-alert')
@include('layouts.components.error-alert')
<div class="card card-info">
    	{!! Form::model($application, ['method' => 'PATCH', 'action' => ['Fsm\ApplicationController@schedulingform', $application->id], 'class' => 'form-horizontal']) !!}
        @include('fsm/emptying-scheduling.partial-form', ['submitButtomText' => 'Save'])
    {!! Form::close() !!}

</div><!-- /.card -->
@stop

@push('scripts')

<script>
$(function () {
  const URL_CAPACITY = `{{ route('applications.capacity-by-date', $application) }}`; // id comes from URL

  const $date = $('#proposed_emptying_date');
  const $form = $date.closest('form');
  const $submit = $form.find('[type=submit]');
  const $msg = $('<small id="capacityMsg" class="form-text mt-1"></small>');
  $date.after($msg);

  function setState(ok, html) {
    $msg.removeClass('text-success text-danger').text('');
    if (html) $msg.addClass(ok ? 'text-success' : 'text-danger').html(html);
    $date[0].setCustomValidity(ok ? '' : ($('<div>').html(html).text() || 'Invalid'));
    $submit.prop('disabled', !ok);
    if (!ok && $date[0].reportValidity) $date[0].reportValidity();
  }

  async function checkCapacity() {
    const v = $date.val();
    if (!v) { setState(true, ''); return; }

    const min = $date.attr('min');
    if (min && v < min) { setState(false, 'Date cannot be in the past.'); return; }

    setState(true, 'Checking capacity…');

    try {
      const r = await $.ajax({
        url: URL_CAPACITY,
        method: 'GET',
        data: { proposed_emptying_date: v },  
        dataType: 'json',
        headers: { 'Accept': 'application/json' }
      });

      if (r.blocked || (+r.available <= 0)) {
        if (r.can_fit === false && +r.available > 0) {
          setState(false, `{{__('Not enough capacity for this application — needs')}} <b>${(+r.vehicle_size).toFixed(2)} m³</b>, {{__('only')}} <b>${(+r.available).toFixed(2)} m³</b> remains.`);
        } else {
          setState(false, `{{__('Blocked — booked')}} <b>${(+r.booked_total).toFixed(2)} m³</b> / {{__('total')}} <b>${(+r.total_capacity).toFixed(2)} m³</b>.`);
        }
      } else {
        setState(true, `{{__('Available treatment plant capacity:')}} <b>${(+r.available).toFixed(2)} m³</b>,{{__('After this application, the remaining capacity will be')}}: <b>${(+r.remaining_after).toFixed(2)} m³</b>.`);
      }
    } catch (xhr) {
      setState(false, 'Could not check capacity.');
      console.error(xhr);
    }
  }

  $date.on('change input', function(){ clearTimeout(this.__t); this.__t=setTimeout(checkCapacity, 200); });
  if ($date.val()) checkCapacity();
});

</script>


@endpush

