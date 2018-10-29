@extends('layouts.app')

@section('scripts')
	<script>
		$(document).ready( function() {
			$('.ui.form')
			  .form({
					/*
					on : 'blur',
					inline : true,
					*/
			    fields: {
			      first_name: {
			        identifier: 'first_name',
			        rules: [
			          {
			            type   : 'empty',
			            prompt : 'Please enter your first name'
			          }
			        ]
			      },
			      last_name: {
			        identifier: 'last_name',
			        rules: [
			          {
			            type   : 'empty',
			            prompt : 'Please enter your last name'
			          }
			        ]
			      },
			      email: {
			        identifier: 'email',
							inline: true,
			        rules: [
			          {
			            type   : 'empty',
			            prompt : 'Please provide an email address'
			          },
								{
			            type   : 'email',
			            prompt : 'Please enter a valid e-mail address'
			          }
			        ]
			      },
			      password: {
			        identifier: 'password',
							optional: true,
			        rules: [
			          {
			            type   : 'empty',
			            prompt : 'Please enter a password'
			          },
			          {
			            type   : 'minLength[6]',
			            prompt : 'Your password must be at least {ruleValue} characters'
			          }
			        ]
			      },
			      password_confirm: {
			        identifier: 'password_confirmation',
							depends: 'password',
			        rules: [
								{
			            type   : 'empty',
			            prompt : 'Please confirm your password'
			          },
								{
			            type   : 'minLength[6]',
			            prompt : 'Your password must be at least {ruleValue} characters'
			          },
								{
			            type   : 'match[password]',
			            prompt : 'Please make sure your password matches'
			          }
			        ]
			      }
			    }
			  })
			;

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
		Profile
		<div class="sub header">View or Update your profile</div>
	</h2>

	@if( isset($status) )
		<div class="ui positive message">
			<i class="close icon"></i>
			<div class="header">
				Profile has been updated
			</div>
		</div>
	@endif

	<form method="POST" action="{{ route('user.profile.update', ['id' => $user->uuid_text]) }}" class="ui form segment">
		@csrf
		<div class="ui centered stackable grid container">
			<div class="left floated row">
			  <div class="four wide column field">
			    <label>First Name</label>
			    <input type="text" id="first_name" name="first_name" placeholder="First Name" value="{{ $user->first_name }}" required autofocus>
			  </div>
			  <div class="four wide column field">
			    <label>Last Name</label>
			    <input type="text" id="last_name" name="last_name" placeholder="Last Name" value="{{ $user->last_name }}" required>
			  </div>
				<div class="four wide column field">
					<label>User Name</label>
					<input type="text" id="user_name" name="user_name" placeholder="User Name" value="{{ $user->user_name }}" required>
				</div>
			</div>
			<div class="row">
				<div class="four wide column field">
			    <label>E-Mail</label>
			    <input type="text" id="email" name="email" placeholder="john.doe@email.com" value="{{ $user->email }}" required>
			  </div>
				<div class="four wide column field">
			    <label>New Password</label>
			    <input type="password" id="password" name="password" autocomplete="off" style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGP6zwAAAgcBApocMXEAAAAASUVORK5CYII=&quot;); cursor: auto;" required>
			  </div>
				<div class="four wide column field">
			    <label>Confirm New Password</label>
			    <input type="password" id="password-confirm" name="password_confirmation" style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGP6zwAAAgcBApocMXEAAAAASUVORK5CYII=&quot;); cursor: auto;" autocomplete="off" required>
			  </div>
			</div>
			<div class="row">
				<div class="four wide column field">
					<label>Job Title</label>
					<input type="text" name="title" id="title" placeholder="Job Title" value="{{ $user->title }}">
				</div>
				<div class="four wide column field">
					<label>Office</label>
					<input type="text" name="office" id="office" placeholder="Office" value="{{ $user->office }}">
				</div>
				<div class="four wide column field">
					<label>Mobile Phone</label>
					<input type="text" name="mobile" id="mobile" placeholder="Mobile Phone" value="{{ $user->mobile}}">
				</div>
			</div>
			<div class="row">
				<div class="twelve wide column field">
			    <label>Address</label>
					<input type="text" name="address" id="address" placeholder="Street Address" value="{{ $user->address }}">
				</div>
			</div>
			<div class="row">
				<div class="four wide column field">
					<label>City</label>
					<input type="text" name="city" id="city" placeholder="City" value="{{ $user->city }}">
				</div>
				<div class="four wide column field">
					<label>State</label>
					<input type="text" name="state" id="state" placeholder="State" value="{{ $user->state }}">
				</div>
				<div class="four wide column field">
					<label>Zip</label>
					<input type="text" name="zip" id="zip" placeholder="Zip Code" value="{{ $user->zip }}">
				</div>
			</div>
			<div class="row">
				<div class="eight wide centered column">
					<div class="ui error message"></div>
				</div>
			</div>
			<div class="row">
				<div class="two wide centered column">
				  <div class="ui primary submit button">{{ __('Save') }}</div>
				</div>
			</div>
		</div>
	</form>

</div>
@endsection
