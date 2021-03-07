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
        <form v-if="user" class="modal-content" @submit.prevent="save()">
          <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
            <button
              type="button"
              class="close"
              aria-label="Close"
              @click="hide()"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body form">
            <div class="form-group">
                <label for="user-edit-modal-name-input">Name</label>
                <input
                    type="text"
                    class="form-control"
                    id="user-edit-modal-name-input"
                    aria-describedby="emailHelp"
                    v-model="user.name"
                />
            </div>
            <div class="form-group">
                <label for="user-edit-modal-role-select">Example select</label>
                <select class="form-control" id="user-edit-modal-role-select" v-model="user.role" :disabled="auth.user.id === user.id">
                    <option :value="null">None</option>
                    <option value="ADMIN">Admin</option>
                </select>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="user-edit-modal-disabled-checkbox" v-model="user.is_disabled" :disabled="auth.user.id === user.id" />
                <label class="form-check-label" for="user-edit-modal-disabled-checkbox">Is disabled?</label>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary" :class="{ 'is-loading': isLoading }">Save changes</button>
            <button type="button" class="btn btn-secondary" @click="hide()">
              Close
            </button>
          </div>
        </form>
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
      user: null,
      isShown: false,
    };
  },
  methods: {
    show(user) {
      this.originalUser = user;
      this.user = { ...user };
      this.isShown = true;
    },
    async save() {
        this.isLoading = true;

        try {
            let response = await axios.post(`/api/users/${this.user.id}/update`, {
                user: this.user,
            });

            Object.assign(this.originalUser, response.data.data);
            this.isLoading = false;
            this.hide();
        } catch (error) {
            this.isLoading = false;
            throw error;
        }
    },
    hide() {
      this.user = null;
      this.isShown = false;
    },
  },
};
</script>
