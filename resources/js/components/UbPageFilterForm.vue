<template>
  <form class="mt-3">
    <div class="d-flex align-items-center">
      <input
        class="form-control"
        placeholder="Page URL"
        name="filter[url]"
        :value="filter.url"
      />
      <input
        class="form-control ml-2"
        placeholder="Form Input Values"
        name="filter[query]"
        :value="filter.query"
      />
    </div>
    <div class="d-flex justify-content-end mt-2">
      <div v-if="doAllowToFilterByUser" style="width: 250px">
        <v-select
          v-model="queryUser"
          class="mr-2"
          placeholder="User Name, ID or Email"
          @search="onUserQueryChanged"
          label="name"
          :filterable="false"
          :options="userQuerySelectOptions"
        ></v-select>
        <input type="hidden" name="filter[user_id]" :value="queryUserId">
      </div>
      <select v-model="perPage" class="custom-select mr-2" style="width: 160px" name="per_page">
        <option :value="25">Show 25 results</option>
        <option :value="50">Show 50 results</option>
        <option :value="100">Show 100 results</option>
      </select>
      <button type="submit" class="btn btn-primary">Search</button>
    </div>
  </form>
</template>

<script>
import { debounce } from 'lodash';

export default {
  props: {
    filter: Object,
    doAllowToFilterByUser: Boolean,
    perPage: Number,
  },
  data() {
    return {
      queryUser: this.filter.user || null,
      userQuerySelectCancel: null,
      userQuerySelectOptions: [],
    };
  },
  computed: {
    queryUserId() {
        return this.queryUser ? this.queryUser.id : 0
    },
  },
  methods: {
    onUserQueryChanged(query, loading) {
      this.userQuerySelectCancel && this.userQuerySelectCancel();

      if (!query) {
          return;
      }

      loading(true);
      this.searchUsers(query, loading);
    },
    searchUsers: debounce(async function (query, loading) {
      try {
        let response = await axios.get('/api/users/autocomplete', {
          params: { query },

          cancelToken: new axios.CancelToken((cancel) => {
            this.userQuerySelectCancel = cancel;
          }),
        });

        this.userQuerySelectOptions = response.data.data;
        loading(false);
      } catch (error) {
        if (this.$axios.isCancel(error)) {
          return;
        }

        loading(false);
        Vue.errorCaptured(error, this);
      }
    }, 250),
  }
};
</script>
