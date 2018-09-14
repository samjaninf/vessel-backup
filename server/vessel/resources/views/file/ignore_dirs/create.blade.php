@extends('layouts.app')

@section('scripts')
	<script>
		$(document).ready( function() {

			$('.ui.form')
			  .form({
			    fields: {
			      directory: {
			        identifier: 'directory',
			        rules: [
			          {
			            type   : 'empty',
			            prompt : 'Please enter a directory'
			          }
			        ]
			      }
			    }
			  });

			$('.message .close')
				.on('click', function() {
					$(this)
						.closest('.message')
						.transition('fade')
					;
				})
			;
		});
	</script>
@endsection

@section('content')

<div class="ui very padded container">

	<h2 class="ui header">
		Add New Ignore Directory
		<div class="sub header"></div>
	</h2>

	@if( isset($error) )
		<div class="ui negative message">
			<i class="close icon"></i>
			<div class="header">
				{{ $error }}
			</div>
		</div>
	@endif

	<form method="POST" action="{{ route('file_ignore_dir.store') }}" class="ui form segment">
		@csrf
		<div class="ui centered stackable grid container">

		  <div class="twelve wide column field">
		    <label>Directory</label>
				<input type="text" id="directory" name="directory" placeholder="Enter Directory" required>
		  </div>

			<div class="row">
				<div class="eight wide centered column">
					<div class="ui error message"></div>
				</div>
			</div>
			<div class="row">
				<div class="two wide centered column">
				  <div class="ui primary submit button">{{ __('Add') }}</div>
				</div>
			</div>

		</div>
	</form>

</div>
@endsection
