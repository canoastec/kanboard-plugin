window.onload = function () {
        var chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: false,
            title:{
                text: "Total horas"
            },
            axisX: {
                maximum: 30,
                minimum: 0
            },
            axisY: {
                titleFontColor: "#4F81BC",
                lineColor: "#4F81BC",
                labelFontColor: "#4F81BC",
                tickColor: "#4F81BC",
                includeZero: false
            },
            toolTip:{
                shared: true
            },
            data: [
            {
                name: "Sprint valor",
                type: "spline",
                showInLegend: true,
                dataPoints: [
                    { x: 0, y: 0 },
                    { x: 20, y: 20 },
                    { x: 30, y: 10 },
                ]
            },
            {
                name: "Sprint valor",
                type: "spline",
                showInLegend: true,
                dataPoints: [
                    { x: 0, y: 0 },
                    { x: 20, y: 14 },
                    { x: 30, y: 11 },
                ]
            }]
        });
        chart.render();
        
        function toggleDataSeries(e){
            if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                e.dataSeries.visible = false;
            }
            else{
                e.dataSeries.visible = true;
            }
            chart.render();
        }
}