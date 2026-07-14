<script setup>
import { onMounted, reactive, ref } from 'vue';
import {
  createComment,
  createTicket,
  fetchMe,
  fetchTicketById,
  fetchTickets,
  getAuthUser,
  login,
  logout,
  updateTicketStatus,
} from './api';

const loading = ref(false);
const saving = ref(false);
const tickets = ref([]);
const selectedTicket = ref(null);
const detailLoading = ref(false);
const statusSaving = ref(false);
const commentSaving = ref(false);
const error = ref('');
const authUser = ref(getAuthUser());

const loginForm = reactive({
  username: 'demo',
  password: 'demo',
});
const loginSaving = ref(false);

const form = reactive({
  submitting_user_id: 1,
  status: 'open',
  title: '',
  body: '',
});

const commentForm = reactive({
  user_id: null,
  commenter_name: 'Guest',
  comment_body: '',
});

async function loadTickets() {
  loading.value = true;
  error.value = '';
  try {
    tickets.value = await fetchTickets();
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function submitTicket() {
  if (!authUser.value) {
    error.value = 'Please login first';
    return;
  }

  saving.value = true;
  error.value = '';
  try {
    await createTicket({
      status: form.status,
      title: form.title,
      body: form.body,
      submitting_user_id: authUser.value.id,
    });
    form.title = '';
    form.body = '';
    await loadTickets();
  } catch (e) {
    error.value = e.message;
  } finally {
    saving.value = false;
  }
}

async function selectTicket(id) {
  detailLoading.value = true;
  error.value = '';
  try {
    selectedTicket.value = await fetchTicketById(id);
  } catch (e) {
    error.value = e.message;
  } finally {
    detailLoading.value = false;
  }
}

async function saveStatus() {
  if (!selectedTicket.value) {
    return;
  }

  statusSaving.value = true;
  error.value = '';
  try {
    selectedTicket.value = await updateTicketStatus(selectedTicket.value.id, selectedTicket.value.status);
    await loadTickets();
  } catch (e) {
    error.value = e.message;
  } finally {
    statusSaving.value = false;
  }
}

async function submitComment() {
  if (!selectedTicket.value) {
    return;
  }

  if (!authUser.value) {
    error.value = 'Please login first';
    return;
  }

  commentSaving.value = true;
  error.value = '';
  try {
    await createComment(selectedTicket.value.id, commentForm);
    commentForm.comment_body = '';
    selectedTicket.value = await fetchTicketById(selectedTicket.value.id);
    await loadTickets();
  } catch (e) {
    error.value = e.message;
  } finally {
    commentSaving.value = false;
  }
}

async function submitLogin() {
  loginSaving.value = true;
  error.value = '';
  try {
    authUser.value = await login(loginForm.username, loginForm.password);
    commentForm.commenter_name = authUser.value.username;
  } catch (e) {
    error.value = e.message;
  } finally {
    loginSaving.value = false;
  }
}

async function restoreAuth() {
  if (!authUser.value) {
    return;
  }

  try {
    authUser.value = await fetchMe();
    commentForm.commenter_name = authUser.value.username;
  } catch {
    logout();
    authUser.value = null;
  }
}

function signOut() {
  logout();
  authUser.value = null;
}

onMounted(async () => {
  await restoreAuth();
  await loadTickets();
});
</script>

<template>
  <main class="page">
    <h1>Support Tickets</h1>
    <p class="meta">Vue frontend talking to CakePHP backend API.</p>

    <section class="panel">
      <h2>Login</h2>
      <p v-if="authUser" class="meta">Logged in as {{ authUser.username }} (ID {{ authUser.id }})</p>
      <div v-if="!authUser" class="row">
        <label>
          Username
          <input v-model="loginForm.username" type="text" />
        </label>
        <label>
          Password
          <input v-model="loginForm.password" type="password" />
        </label>
      </div>
      <button v-if="!authUser" :disabled="loginSaving" @click="submitLogin">
        {{ loginSaving ? 'Signing in...' : 'Login' }}
      </button>
      <button v-else @click="signOut">Logout</button>
    </section>

    <section class="panel">
      <h2>Create Ticket</h2>
      <div class="row">
        <label>
          Status
          <select v-model="form.status">
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="closed">Closed</option>
          </select>
        </label>
      </div>
      <div class="row">
        <label>
          Title
          <input v-model="form.title" type="text" placeholder="Issue summary" />
        </label>
      </div>
      <div class="row">
        <label>
          Body
          <textarea v-model="form.body" placeholder="Describe the issue"></textarea>
        </label>
      </div>
      <button :disabled="saving" @click="submitTicket">
        {{ saving ? 'Saving...' : 'Submit Ticket' }}
      </button>
    </section>

    <section class="panel">
      <h2>Recent Tickets</h2>
      <p v-if="loading">Loading...</p>
      <p v-if="error" class="meta">{{ error }}</p>
      <div class="list">
        <article v-for="ticket in tickets" :key="ticket.id" class="ticket">
          <h3>{{ ticket.title }}</h3>
          <p>{{ ticket.body }}</p>
          <p class="meta">
            #{{ ticket.id }} | User {{ ticket.submitting_user_id }} | {{ ticket.status }}
          </p>
          <button @click="selectTicket(ticket.id)">View Ticket</button>
        </article>
      </div>
    </section>

    <section class="panel" v-if="selectedTicket">
      <h2>Ticket Detail</h2>
      <p v-if="detailLoading">Loading detail...</p>
      <template v-else>
        <h3>{{ selectedTicket.title }}</h3>
        <p>{{ selectedTicket.body }}</p>
        <p class="meta">Ticket #{{ selectedTicket.id }} | User {{ selectedTicket.submitting_user_id }}</p>
        <div class="row">
          <label>
            Status
            <select v-model="selectedTicket.status">
              <option value="open">Open</option>
              <option value="in_progress">In Progress</option>
              <option value="closed">Closed</option>
            </select>
          </label>
        </div>
        <button :disabled="statusSaving" @click="saveStatus">
          {{ statusSaving ? 'Saving...' : 'Update Status' }}
        </button>

        <hr />

        <h3>Comments</h3>
        <div class="list" v-if="selectedTicket.comments?.length">
          <article v-for="comment in selectedTicket.comments" :key="comment.id" class="ticket">
            <strong>{{ comment.commenter_name }}</strong>
            <p>{{ comment.comment_body }}</p>
          </article>
        </div>
        <p v-else class="meta">No comments yet.</p>

        <div class="row">
          <p class="meta">Commenting as {{ authUser?.username || 'Guest' }}</p>
        </div>
        <div class="row">
          <label>
            Comment
            <textarea v-model="commentForm.comment_body" placeholder="Add a reply"></textarea>
          </label>
        </div>
        <button :disabled="commentSaving" @click="submitComment">
          {{ commentSaving ? 'Saving...' : 'Add Comment' }}
        </button>
      </template>
    </section>
  </main>
</template>
