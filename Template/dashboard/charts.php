<body>
	<div id="app">

		<chart-vue :data='<?= $timeChart ?>'></chart-vue>

		<chart-vue :data='<?= $tasksChart ?>'></chart-vue>
		
		<chart-vue :data='<?= $percentageChart ?>'></chart-vue>

	</div>
</body>
<script type="module">
	new Vue({
		el: "#app"
	});
</script>