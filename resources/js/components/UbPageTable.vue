<template>
  <div>
    <table class="my-3 table">
        <thead>
          <th>ID</th>
          <th v-if="doShowUserColumn">User</th>
          <th>Date</th>
          <th class="text-nowrap">Visitor ID</th>
          <th>URL (in new tab)</th>
          <th>Title</th>
          <th>Duration</th>
          <th></th>
        </thead>
        <tbody>
          <tr v-for="ubPage in ubPages" :key="ubPage.id">
            <td>{{ ubPage.id }}</td>
            <td v-if="doShowUserColumn">
                <span class="text-nowrap" :class="{ 'font-italic': !ubPage.user }">
                    {{ ubPage.user ? ubPage.user.name : 'No User' }}
                </span>
                <span v-if="ubPage.user" class="text-nowrap">(ID: {{ ubPage.user.id }})</span>
            </td>
            <td style="max-width: 230px; overflow: hidden">
              {{ $moment(ubPage.created_at).format("LLLL") }}
            </td>
            <td>{{ ubPage.visitor_id }}</td>
            <td style="
              max-width: 300px;
              overflow: hidden;
              white-space: nowrap;
              text-overflow: ellipsis;
            ">
              <a :href="ubPage.url" target="_blank">{{ ubPage.url }}</a>
            </td>
            <td>{{ ubPage.title }}</td>
            <td>{{ $durationToString(ubPage.duration) }}</td>
            <td class="d-flex justify-content-end align-items-center">
              <a :href="`/dashboard/ub-pages/${ubPage.id}`" class="btn btn-primary">
                  Details
              </a>
              <button v-if="doAllowToDelete" class="btn btn-danger ml-2" @click="$refs.deleteConfirmModal.show(ubPage)">Delete</button>
            </td>
          </tr>
        </tbody>
    </table>
    <ub-page-delete-confirm-modal ref="deleteConfirmModal" @deleted="onUbPageDeleted" />
  </div>
</template>

<script>
export default {
  props: {
    doShowUserColumn: Boolean,
    doAllowToDelete: Boolean,
  },
  data() {
    return {
      ubPages: window.ubPages,
    };
  },
  methods: {
    onUbPageDeleted(ubPage) {
        let ubPageIndex = this.ubPages.indexOf(ubPage);

        if (ubPageIndex > -1) {
            this.ubPages.splice(ubPageIndex, 1);
        }
    },
  },
};
</script>
