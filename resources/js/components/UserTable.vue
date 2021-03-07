<template>
  <div>
    <table class="my-3 table">
      <thead>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Status</th>
        <th>Role</th>
        <th>Registered At</th>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td>{{ user.id }}</td>
          <td>{{ user.name }}</td>
          <td>{{ user.email }}</td>
          <td :class="{ 'text-danger': user.is_disabled, 'text-success': !user.is_disabled }">
            {{ user.is_disabled ? 'Disabled' : 'Active' }}
          </td>
          <td :class="{ 'font-italic' : !user.role, 'text-secondary': !user.role }">{{ user.role || 'NO' }}</td>
          <td>{{ $moment(user.created_at).format('LLLL') }}</td>
          <td class="text-right">
            <button
              class="btn btn-primary"
              @click="editUser(user)"
            >
              Edit
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <user-edit-modal ref="userEditModal" />
  </div>
</template>

<script>
export default {
  mounted() {
    console.log("Component mounted.");
  },
  data() {
    return {
        users: window.users,
    };
  },
  methods: {
    editUser(user) {
        this.$refs.userEditModal.show(user);
    },
  },
};
</script>
