@extends('layouts.app')

@section('content')
<div class="clearfix"></div>
<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12">
		<div class="x_panel">
			<div class="x_content">
				<div class="form-group">
					<textarea readonly rows="40" class="form-control" id="consola"></textarea>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('scripts')
	<script>
		$(function () {
			setInterval(function () {
				$.get("{{ url('consola') }}", function (data) {
					var consola = $('#consola');
					for (var i = 0; i < data.length; i++)
						consola.append(data[i].created_at + ': ' + data[i].contenido + '\n');
				});
			}, 10000);
		});
	</script>
@endsection