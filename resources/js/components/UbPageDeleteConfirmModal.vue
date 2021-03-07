<template>
  <div>
    <div
      class="modal"
      :class="{ 'd-block': isShown }"
      tabindex="-1"
      role="dialog"
      @click="hide()"
    >
      <div class="modal-dialog" role="document" @click.stop>
        <div v-if="ubPage" class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmation</h5>
            <button
              type="button"
              class="close"
              aria-label="Close"
              @click="hide()"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this Page?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" :class="{ 'is-loading': isLoading }" @click="deleteUbPage()">Delete</button>
            <button type="button" class="btn btn-secondary" @click="hide()">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="isShown" class="modal-backdrop fade show"></div>
  </div>
</template>

<script>
export default {
  mounted() {
    console.log("Component mounted.");
  },
  data() {
    return {
      isLoading: false,
      auth: window.auth,
      ubPage: null,
      isShown: false,
    };
  },
  methods: {
    show(ubPage) {
      this.ubPage = ubPage;
      this.isShown = true;
    },
    async deleteUbPage() {
        this.isLoading = true;

        try {
            await axios.delete(`/api/ub/pages/${this.ubPage.id}`);
            this.isLoading = false;
            this.$emit('deleted', this.ubPage);
            this.hide();
        } catch (error) {
            this.isLoading = false;
            throw error;
        }
    },
    hide() {
      this.ubPage = null;
      this.isShown = false;
    },
  },
};
</script>
