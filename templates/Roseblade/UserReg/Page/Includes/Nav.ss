<div class="container mb-4">
	<div class="row">
		<div class="col-12">
			<nav class="nav nav-pills">
			<% loop $AccountMenu %>	
				<li class="nav-item">
					<a class="nav-link<% if $LinkingMode == "current" %> active" aria-current="page<% end_if %>" href="{$Link}">{$Title}</a>
				</li>
			<% end_loop %>
			</nav>
		</div>
	</div>
</div>