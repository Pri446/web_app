<table>
    <thead>
        <tr>
            <th>{{__('CWIS Monitoring and Evaluation Indicators')}}</th>
        </tr>
        <tr>
            <th> {{ $years }}</th>
        </tr>
        <tr>
            <th> </th>
        </tr>
        <tr>
            <th>{{__('Indicators')}} </th>
            <th>{{__('Outcome')}}</th>
            <th>{{__('Value')}}</th>
        </tr>

    </thead>
    <tbody>
        @foreach ($results as $result)
            <tr>
                <td>{{ $result->label}}</td>
                <td> {{ $result->outcome === 'rpm' ? __('Resource Planning and Management') : $result->outcome }}</td>
                <td>{{ $result->data_value }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
