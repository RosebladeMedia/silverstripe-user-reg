<% include Roseblade\UserReg\Page\Nav %>

<div class="container">
	<div class="row">
		<div class="col-12">
			<h1>{$Title}</h1>
		</div>
	</div>
</div>

<% if $RequireVerification %>
	<div class="container">
		<div class="row">
			<div class="col-12 mt-4">
				<div class="alert alert-danger">
					<p>
						<% _t("Roseblade\UserReg.NOTVERIFIED", "You have not verified your email address") %>
						<a href="{$RegistrationPage.Link}/sendverification">
							<% _t("Roseblade\UserReg.SEND", "Send now") %>
						</a>
					</p>
				</div>
			</div>
		</div>
	</div>
<% end_if %>

<section class="container">
	<div class="row">
		<div class="col-12">
			<% with $CurrentUser %>
				<p>
					<strong><%t SilverStripe\Security\Member.Member.FIRSTNAME "First Name" %></strong>: {$FirstName}<br/>
					<strong><%t SilverStripe\Security\Member.Member.SURNAME "Surname" %></strong>: {$Surname}<br/>
					<strong><%t SilverStripe\Security\Member.Member.EMAIL "Email" %></strong>: {$Email}<br/>
					<strong><%t Users.FirstRegistered "First Registered" %></strong>: {$Created.Ago}<br/>
				</p>
			<% end_with %>
		</div>
	</div>
</section>