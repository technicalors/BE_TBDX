<html>
<!-- CSS only -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">


<table id='table' class='table text-center'>
    <thead>
        <th>#</th>
        <th>Thời gian gửi</th>
        <th>Data</th>
    </thead>
        @foreach($logs as $key=>$log)
            <tr>
                <td>{{$key + 1}}</td>
                <td>{{date('d/m/Y H:i:s',strtotime($log->created_at))}}</td>
                <td>{!!json_encode($log->data)!!}</td>
            </tr>
        @endforeach
    <tbody>

    </tbody>
</table>

<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"
    integrity="sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</html>
