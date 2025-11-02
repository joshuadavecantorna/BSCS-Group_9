<template>
  <Head title="Excuse Requests" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-8">
      <!-- Page Title -->
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-white">Excuse Requests</h1>
        <p class="text-gray-400">Review and manage student excuse requests for your classes</p>
      </div>

      <!-- Stats-like Header Section -->
      <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 text-center">
          <p class="text-gray-400 text-sm">Total Requests</p>
          <p class="text-2xl font-bold text-white">{{ requests.total }}</p>
        </div>
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 text-center">
          <p class="text-gray-400 text-sm">Pending</p>
          <p class="text-2xl font-bold text-yellow-400">{{ requests.data.filter(r => r.status === 'pending').length }}</p>
        </div>
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 text-center">
          <p class="text-gray-400 text-sm">Approved</p>
          <p class="text-2xl font-bold text-green-400">{{ requests.data.filter(r => r.status === 'approved').length }}</p>
        </div>
        <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800 text-center">
          <p class="text-gray-400 text-sm">Rejected</p>
          <p class="text-2xl font-bold text-red-400">{{ requests.data.filter(r => r.status === 'rejected').length }}</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-gray-900 rounded-2xl p-6 border border-gray-800">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-400 mb-2">Status</label>
            <select
              v-model="filters.status"
              @change="fetchRequests"
              class="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-gray-200 focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-400 mb-2">Class</label>
            <select
              v-model="filters.class_id"
              @change="fetchRequests"
              class="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-gray-200 focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">All Classes</option>
              <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id">
                {{ classItem.name }} - {{ classItem.course }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-400 mb-2">Search</label>
            <input
              v-model="filters.search"
              @input="debounceSearch"
              type="text"
              placeholder="Search by student name..."
              class="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-2 text-gray-200 placeholder-gray-500 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>
      </div>

      <!-- Requests List -->
      <div>
        <h2 class="text-xl font-semibold text-white mb-4">Excuse Request List</h2>

        <div v-if="loading" class="p-8 text-center text-gray-400">
          Loading requests...
        </div>

        <div v-else-if="requests.data.length === 0" class="p-8 text-center text-gray-400">
          No excuse requests to review at this time.
        </div>

        <div v-else class="grid grid-cols-1 gap-4">
          <div
            v-for="request in requests.data"
            :key="request.id"
            class="bg-gray-900 border border-gray-800 rounded-2xl p-6 hover:border-indigo-500 transition"
          >
            <div class="flex justify-between items-start">
              <div>
                <h3 class="text-lg font-medium text-white mb-1">
                  {{ request.student.name }}
                </h3>
                <span
                  :class="getStatusBadgeClass(request.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mb-3"
                >
                  {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                </span>
                <div class="text-sm text-gray-400 space-y-1">
                  <p><b class="text-gray-300">Student ID:</b> {{ request.student.student_id }}</p>
                  <p><b class="text-gray-300">Class:</b> {{ request.attendance_session.class.name }} - {{ request.attendance_session.class.course }}</p>
                  <p><b class="text-gray-300">Session:</b> {{ request.attendance_session.session_name }}</p>
                  <p><b class="text-gray-300">Date:</b> {{ formatDate(request.attendance_session.session_date) }}</p>
                  <p><b class="text-gray-300">Reason:</b> {{ request.reason }}</p>
                  <p v-if="request.review_notes"><b class="text-gray-300">Review Notes:</b> {{ request.review_notes }}</p>
                </div>
              </div>

              <div>
                <div v-if="request.status === 'pending'" class="flex space-x-2">
                  <button
                    @click="showApproveModal(request)"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                  >
                    Approve
                  </button>
                  <button
                    @click="showRejectModal(request)"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                  >
                    Reject
                  </button>
                </div>
                <div v-else class="text-sm text-gray-400 mt-2">
                  {{ request.status === 'approved' ? 'Approved' : 'Rejected' }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Existing Modals (unchanged) -->
    <!-- Keep your modal code as-is -->
  </AppLayout>
</template>


<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

export default {
  name: 'ExcuseRequests',
  props: {
    teacher: Object,
    requests: Object,
    classes: Array
  },
  setup(props) {
    const breadcrumbs = ref([
      { title: 'Dashboard', url: '/teacher/dashboard' },
      { title: 'Excuse Requests', url: null }
    ])

    const loading = ref(false)
    const processing = ref(false)
    const showApproveModalFlag = ref(false)
    const showRejectModalFlag = ref(false)
    const selectedRequest = ref(null)
    const reviewNotes = ref('')

    const filters = ref({
      status: '',
      class_id: '',
      search: ''
    })

    const requests = ref(props.requests)
    const classes = ref(props.classes || [])

    const fetchRequests = async () => {
      loading.value = true
      try {
        const params = new URLSearchParams()
        if (filters.value.status) params.append('status', filters.value.status)
        if (filters.value.class_id) params.append('class_id', filters.value.class_id)
        if (filters.value.search) params.append('search', filters.value.search)

        const response = await axios.get(`/teacher/excuse-requests?${params}`)
        requests.value = response.data.requests
      } catch (error) {
        console.error('Error fetching requests:', error)
      } finally {
        loading.value = false
      }
    }

    const debounceSearch = (() => {
      let timeout
      return () => {
        clearTimeout(timeout)
        timeout = setTimeout(fetchRequests, 500)
      }
    })()

    const changePage = (page) => {
      // Implement pagination
      fetchRequests()
    }

    const showApproveModal = (request) => {
      selectedRequest.value = request
      reviewNotes.value = ''
      showApproveModalFlag.value = true
    }

    const showRejectModal = (request) => {
      selectedRequest.value = request
      reviewNotes.value = ''
      showRejectModalFlag.value = true
    }

    const closeModals = () => {
      showApproveModalFlag.value = false
      showRejectModalFlag.value = false
      selectedRequest.value = null
      reviewNotes.value = ''
    }

    const approveRequest = async () => {
      if (!selectedRequest.value) return

      processing.value = true
      try {
        await axios.post(`/teacher/excuse-requests/${selectedRequest.value.id}/approve`, {
          review_notes: reviewNotes.value
        })

        closeModals()
        fetchRequests()
        // Show success message
      } catch (error) {
        console.error('Error approving request:', error)
        // Show error message
      } finally {
        processing.value = false
      }
    }

    const rejectRequest = async () => {
      if (!selectedRequest.value) return

      processing.value = true
      try {
        await axios.post(`/teacher/excuse-requests/${selectedRequest.value.id}/reject`, {
          review_notes: reviewNotes.value
        })

        closeModals()
        fetchRequests()
        // Show success message
      } catch (error) {
        console.error('Error rejecting request:', error)
        // Show error message
      } finally {
        processing.value = false
      }
    }

    const getStatusBadgeClass = (status) => {
      switch (status) {
        case 'pending':
          return 'bg-yellow-100 text-yellow-900 border border-yellow-300'
        case 'approved':
          return 'bg-green-100 text-green-900 border border-green-300'
        case 'rejected':
          return 'bg-red-100 text-red-900 border border-red-300'
        default:
          return 'bg-gray-100 text-gray-900 border border-gray-300'
      }
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const formatDateTime = (dateTime) => {
      return new Date(dateTime).toLocaleString()
    }

    onMounted(() => {
      // Fetch classes if not provided
      if (classes.value.length === 0) {
        fetchClasses()
      }
    })

    const fetchClasses = async () => {
      try {
        const response = await axios.get('/teacher/classes')
        classes.value = response.data.classes || []
      } catch (error) {
        console.error('Error fetching classes:', error)
      }
    }

    return {
      loading,
      processing,
      showApproveModalFlag,
      showRejectModalFlag,
      selectedRequest,
      reviewNotes,
      filters,
      requests,
      classes,
      fetchRequests,
      debounceSearch,
      changePage,
      showApproveModal,
      showRejectModal,
      closeModals,
      approveRequest,
      rejectRequest,
      getStatusBadgeClass,
      formatDate,
      formatDateTime
    }
  }
}
</script>
