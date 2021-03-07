/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');
Vue.use(require('vue-moment'));
import vSelect from 'vue-select';

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('v-select', vSelect);
Vue.component('user-edit-modal', require('./components/UserEditModal.vue').default);
Vue.component('user-table', require('./components/UserTable.vue').default);
Vue.component('ub-page-table', require('./components/UbPageTable.vue').default);
Vue.component('ub-page-filter-form', require('./components/UbPageFilterForm.vue').default);
Vue.component('ub-page-delete-confirm-modal', require('./components/UbPageDeleteConfirmModal.vue').default);

/**
 * Custom Vue Functions
 */

Vue.prototype.$durationToString = function (duration) {
    let totalSeconds = Math.ceil(duration / 1000);
    let totalMinutes = Math.floor(totalSeconds / 60);
    let seconds = totalSeconds - totalMinutes * 60;
    let totalHours = Math.floor(totalMinutes / 60);
    let minutes = totalMinutes - totalHours * 60;
    let hours = totalHours;

    return `${hours}:${String(minutes).padStart(2, "0")}:${String(
        seconds
    ).padStart(2, "0")}`;
};

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
