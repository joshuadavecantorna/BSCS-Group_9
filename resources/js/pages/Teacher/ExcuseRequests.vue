<template>
  <Head title="Excuse Requests" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      <!-- Page Header -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Excuse Requests</h1>
        <p class="text-muted-foreground">
          Review and manage student excuse requests for your classes
        </p>
      </div>

      <!-- Statistics Overview -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Requests</CardTitle>
            <span class="text-xl">📋</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ requests.total }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              All requests
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <span class="text-xl">⏳</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ requests.data.filter(r => r.status === 'pending').length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Awaiting review
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Approved</CardTitle>
            <span class="text-xl">✅</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ requests.data.filter(r => r.status === 'approved').length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Approved requests
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Rejected</CardTitle>
            <span class="text-xl">❌</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ requests.data.filter(r => r.status === 'rejected').length }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Rejected requests
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
          <CardDescription>Filter excuse requests by status, class, or student name</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="space-y-2">
              <Label for="status-filter">Status</Label>
              <select
                id="status-filter"
                v-model="filters.status"
                @change="fetchRequests"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="class-filter">Class</Label>
              <select
                id="class-filter"
                v-model="filters.class_id"
                @change="fetchRequests"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <option value="">All Classes</option>
                <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id.toString()">
                  {{ classItem.name }} - {{ classItem.course }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <Label for="search-filter">Search</Label>
              <Input
                id="search-filter"
                v-model="filters.search"
                @input="debounceSearch"
                type="text"
                placeholder="Search by student name..."
                class="w-full"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Requests List -->
      <Card>
        <CardHeader>
          <CardTitle>Excuse Requests</CardTitle>
          <CardDescription>Review and manage student excuse requests</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="loading" class="p-8 text-center text-muted-foreground">
            Loading requests...
          </div>

          <div v-else-if="requests.data.length === 0" class="p-8 text-center text-muted-foreground">
            No excuse requests to review at this time.
          </div>

          <div v-else class="space-y-4">
            <Card
              v-for="request in requests.data"
              :key="request.id"
              class="transition-all hover:shadow-md"
            >
              <CardContent class="p-6">
                <div class="flex justify-between items-start mb-4">
                  <div>
                    <h3 class="text-lg font-medium mb-2">
                      {{ request.student.name }}
                    </h3>
                    <Badge :variant="getStatusBadgeVariant(request.status)" class="mb-3">
                      {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                    </Badge>
                  </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div class="space-y-2">
                    <p><span class="font-medium">Student ID:</span> {{ request.student.student_id }}</p>
                    <p><span class="font-medium">Class:</span> {{ request.attendance_session.class.name }} - {{ request.attendance_session.class.course }}</p>
                    <p><span class="font-medium">Session:</span> {{ request.attendance_session.session_name }}</p>
                    <p><span class="font-medium">Date:</span> {{ formatDate(request.attendance_session.session_date) }}</p>
                  </div>
                  <div class="space-y-2">
                    <p><span class="font-medium">Reason:</span> {{ request.reason }}</p>
                    <p v-if="request.review_notes"><span class="font-medium">Review Notes:</span> {{ request.review_notes }}</p>
                    <p v-if="request.reviewed_at"><span class="font-medium">Reviewed:</span> {{ formatDateTime(request.reviewed_at) }}</p>
                  </div>
                </div>

                <Separator class="my-4" />

                <div class="flex flex-col sm:flex-row gap-2 justify-between items-start sm:items-center">
                  <div>
                    <a
                      v-if="request.attachment_path"
                      :href="`/teacher/excuse-requests/${request.id}/download-attachment`"
                      target="_blank"
                      class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
                    >
                      📎 Download Attachment
                    </a>
                  </div>
                  
                  <div v-if="request.status === 'pending'" class="flex space-x-2">
                    <Button
                      @click="showApproveModal(request)"
                      variant="default"
                      size="sm"
                      class="bg-green-600 hover:bg-green-700"
                    >
                      ✓ Approve
                    </Button>
                    <Button
                      @click="showRejectModal(request)"
                      variant="destructive"
                      size="sm"
                    >
                      ✗ Reject
                    </Button>
                  </div>
                  
                  <div v-else class="text-sm text-muted-foreground">
                    {{ request.status === 'approved' ? '✓ Approved' : '✗ Rejected' }}
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Approve Modal -->
    <Dialog v-model:open="showApproveModalFlag">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Approve Excuse Request</DialogTitle>
          <DialogDescription>
            Are you sure you want to approve this excuse request from {{ selectedRequest?.student?.name }}?
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="approve-notes">Review Notes (Optional)</Label>
            <Textarea
              id="approve-notes"
              v-model="reviewNotes"
              rows="3"
              placeholder="Add any notes about your approval..."
            />
          </div>
        </div>
        <DialogFooter>
          <Button
            variant="outline"
            @click="closeModals"
            :disabled="processing"
          >
            Cancel
          </Button>
          <Button
            @click="approveRequest"
            :disabled="processing"
            class="bg-green-600 hover:bg-green-700"
          >
            {{ processing ? 'Approving...' : 'Approve' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Reject Modal -->
    <Dialog v-model:open="showRejectModalFlag">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Reject Excuse Request</DialogTitle>
          <DialogDescription>
            Are you sure you want to reject this excuse request from {{ selectedRequest?.student?.name }}?
          </DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="reject-notes">Review Notes (Required)</Label>
            <Textarea
              id="reject-notes"
              v-model="reviewNotes"
              rows="3"
              placeholder="Please provide a reason for rejection..."
              required
            />
          </div>
        </div>
        <DialogFooter>
          <Button
            variant="outline"
            @click="closeModals"
            :disabled="processing"
          >
            Cancel
          </Button>
          <Button
            variant="destructive"
            @click="rejectRequest"
            :disabled="processing || !reviewNotes.trim()"
          >
            {{ processing ? 'Rejecting...' : 'Reject' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>


<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { router, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'

// Props interface
interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
  };
  requests: {
    total: number;
    data: Array<{
      id: number;
      status: 'pending' | 'approved' | 'rejected';
      reason: string;
      review_notes?: string;
      reviewed_at?: string;
      attachment_path?: string;
      student: {
        id: number;
        student_id: string;
        name: string;
      };
      attendance_session: {
        id: number;
        session_name: string;
        session_date: string;
        class: {
          id: number;
          name: string;
          course: string;
        };
      };
    }>;
  };
  classes: Array<{
    id: number;
    name: string;
    course: string;
  }>;
}

const props = defineProps<Props>();

// Component state
const breadcrumbs = ref([
  { title: 'Dashboard', href: '/teacher/dashboard' },
  { title: 'Excuse Requests', href: '/teacher/excuse-requests' }
]);

const loading = ref(false);
const processing = ref(false);
const showApproveModalFlag = ref(false);
const showRejectModalFlag = ref(false);
const selectedRequest = ref<Props['requests']['data'][0] | null>(null);
const reviewNotes = ref('');

const filters = ref({
  status: '',
  class_id: '',
  search: ''
});

const requests = ref(props.requests);
const classes = ref(props.classes || []);

// Functions
const fetchRequests = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.class_id) params.append('class_id', filters.value.class_id);
    if (filters.value.search) params.append('search', filters.value.search);

    const response = await axios.get(`/teacher/excuse-requests?${params}`);
    requests.value = response.data.requests;
  } catch (error) {
    console.error('Error fetching requests:', error);
  } finally {
    loading.value = false;
  }
};

const debounceSearch = (() => {
  let timeout: NodeJS.Timeout;
  return () => {
    clearTimeout(timeout);
    timeout = setTimeout(fetchRequests, 500);
  };
})();

const showApproveModal = (request: Props['requests']['data'][0]) => {
  selectedRequest.value = request;
  reviewNotes.value = '';
  showApproveModalFlag.value = true;
};

const showRejectModal = (request: Props['requests']['data'][0]) => {
  selectedRequest.value = request;
  reviewNotes.value = '';
  showRejectModalFlag.value = true;
};

const closeModals = () => {
  showApproveModalFlag.value = false;
  showRejectModalFlag.value = false;
  selectedRequest.value = null;
  reviewNotes.value = '';
};

const approveRequest = async () => {
  if (!selectedRequest.value) return;

  processing.value = true;
  try {
    await axios.post(`/teacher/excuse-requests/${selectedRequest.value.id}/approve`, {
      review_notes: reviewNotes.value
    });

    closeModals();
    fetchRequests();
    // Show success message using Inertia flash
    router.visit(window.location.pathname, {
      method: 'get',
      data: {},
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        // Flash message will be handled by the backend
      }
    });
  } catch (error) {
    console.error('Error approving request:', error);
    alert('Failed to approve request. Please try again.');
  } finally {
    processing.value = false;
  }
};

const rejectRequest = async () => {
  if (!selectedRequest.value || !reviewNotes.value.trim()) return;

  processing.value = true;
  try {
    await axios.post(`/teacher/excuse-requests/${selectedRequest.value.id}/reject`, {
      review_notes: reviewNotes.value
    });

    closeModals();
    fetchRequests();
    // Show success message using Inertia flash
    router.visit(window.location.pathname, {
      method: 'get',
      data: {},
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        // Flash message will be handled by the backend
      }
    });
  } catch (error) {
    console.error('Error rejecting request:', error);
    alert('Failed to reject request. Please try again.');
  } finally {
    processing.value = false;
  }
};

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'pending':
      return 'secondary';
    case 'approved':
      return 'default';
    case 'rejected':
      return 'destructive';
    default:
      return 'outline';
  }
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString();
};

const formatDateTime = (dateTime: string) => {
  return new Date(dateTime).toLocaleString();
};

const fetchClasses = async () => {
  try {
    const response = await axios.get('/teacher/classes');
    classes.value = response.data.classes || [];
  } catch (error) {
    console.error('Error fetching classes:', error);
  }
};

onMounted(() => {
  // Fetch classes if not provided
  if (classes.value.length === 0) {
    fetchClasses();
  }
});
</script>
