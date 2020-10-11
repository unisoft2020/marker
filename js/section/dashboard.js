add_event(document, 'DOMContentLoaded', function() { dashboard.init(); });

var dashboard = {
	
	init: function() {
		am4core.ready(function() {
			// init
			am4core.useTheme(am4themes_frozen);
			am4core.useTheme(am4themes_animated);
			// chart
			var chart = am4core.create("chart_tasks", am4charts.PieChart);
			chart.innerRadius = am4core.percent(60);
			// data
			chart.data = [
				{"title": "Новые", "count": global.users_new},
				{"title": "Активные", "count": global.users_active},
				{"title": "Не активные", "count": global.users_inactive},
				{"title": "Заблокированы", "count": global.users_blocked}
			];
			// configure
			var series = chart.series.push(new am4charts.PieSeries());
			series.dataFields.value = "count";
			series.dataFields.category = "title";
			series.dataFields.hidden = "show";
			// disable ticks, labels & tooltips
			series.labels.template.disabled = true;
			series.ticks.template.disabled = true;
			series.slices.template.tooltipText = "";
			// put a thick white border around each slice
			series.slices.template.stroke = am4core.color("#fff");
			series.slices.template.strokeWidth = 2;
			series.slices.template.strokeOpacity = 1;
			series.slices.template.cursorOverStyle = [{"property": "cursor", "value": "pointer"}];
			//series.slices.template.cornerRadius = 6;
			//series.alignLabels = false;
			//series.labels.template.bent = true;
			//series.labels.template.radius = 3;
			//series.labels.template.padding(0,0,0,0);
			//series.ticks.template.disabled = true;
			// label
			var label = chart.seriesContainer.createChild(am4core.Label);
			label.textAlign = "middle";
			label.horizontalCenter = "middle";
			label.verticalCenter = "middle";
			label.adapter.add("text", function(text, target) {
				return "[bold font-size:22px]" + series.dataItem.values.value.sum + "[/]\n[font-size:12px]всего[/]";
			})
		});
		am4core.ready(function() {
			// init
			am4core.useTheme(am4themes_frozen);
			am4core.useTheme(am4themes_animated);
			// chart
			var chart = am4core.create("chart_products", am4charts.PieChart);
			chart.innerRadius = am4core.percent(60);
			// data
			chart.data = [
				{"title": "Использовано", "count": global.size_used},
				{"title": "Свободно", "count": global.size_free}
			];
			// configure
			var series = chart.series.push(new am4charts.PieSeries());
			series.dataFields.value = "count";
			series.dataFields.category = "title";
			series.dataFields.hidden = "show";
			// disable ticks, labels & tooltips
			series.labels.template.disabled = true;
			series.ticks.template.disabled = true;
			series.slices.template.tooltipText = "";
			// put a thick white border around each slice
			series.slices.template.stroke = am4core.color("#fff");
			series.slices.template.strokeWidth = 2;
			series.slices.template.strokeOpacity = 1;
			series.slices.template.cursorOverStyle = [{"property": "cursor", "value": "pointer"}];
			//series.slices.template.cornerRadius = 6;
			//series.alignLabels = false;
			//series.labels.template.bent = true;
			//series.labels.template.radius = 3;
			//series.labels.template.padding(0,0,0,0);
			//series.ticks.template.disabled = true;
			// label
			var label = chart.seriesContainer.createChild(am4core.Label);
			label.textAlign = "middle";
			label.horizontalCenter = "middle";
			label.verticalCenter = "middle";
			label.adapter.add("text", function(text, target) {
				return "[bold font-size:22px]" + global.size_total + " Гб[/]\n[font-size:12px]всего[/]";
			})
		});
	},
	
	stats_export: function() {
		// vars
		var location = { dpt: 'dashboard', sub: 'stats', act: 'export' };
		// call
		request_file({ location: location }, function(result) {
			download_file('export.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', result);
		});
	},

}
