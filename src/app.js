import Vue from 'vue';
window.Vue = Vue;

import Chartkick from 'vue-chartkick'
import Chart from 'chart.js'

Vue.use(Chartkick.use(Chart));

import Teste from '../src/Asset/Components/charts.vue';
Vue.component('chart-vue', Teste);
