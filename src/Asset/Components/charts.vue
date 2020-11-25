<template>
    <div class="chart-vue">
      <h1 class="chart-title-vue">{{data.title}}</h1>
      <line-chart :data="chart"></line-chart>
    </div>
</template>
<script>
  export default {
    props: [
      'data'
    ],
    data: function(){
      return {
        timeCharts: null,
        chartData: null,
      }
    },
    created: function(){
    },
    methods: {
    },
    computed: {
      chart: function(){
        this.timeCharts = this.data.datasets;
        return this.chartData = this.timeCharts.map(function(data){
          return {
            name: data.label,
            color: data.color,
            data: data.data.map((data) => { return [data.name, data.data] }),
          };
        });
      }
    }
  }
</script>
<style>
  .chart-title-vue{
    font-weight: bold;
    text-align: center;
  }
  .chart-vue{
    margin: 20px 150px 10px 10px;
  }
</style>