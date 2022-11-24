<?php namespace tuja\admin;

$this->print_root_menu();
$this->print_menu();
?>
<div id="search-container">
	<div id="search-query-container">
		<input 
			type="text" 
			class="text" 
			id="search-input-field" 
			placeholder="Sök grupp eller person" 
			data-query-endpoint="<?= $search_query_endpoint ?>"
			data-group-page-url-pattern="<?= $group_page_url_pattern ?>"
			>
	</div>
	<div id="search-result-pending">
		<div class="spinner is-active"></div>
	</div>
	<div id="search-result-container">
		<div id="search-result-people-container">
			<h3>Personer</h3>
			<table id="search-result-people" class="tuja-table"></table>
		</div>
		<div id="search-result-groups-container">
			<h3>Grupper</h3>
			<table id="search-result-groups" class="tuja-table"></table>
		</div>
		<div id="search-result-empty">Inga träffar</div>
	</div>
</div>
