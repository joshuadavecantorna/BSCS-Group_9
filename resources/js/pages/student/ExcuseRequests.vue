<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
// import { Textarea } from '@/components/ui/textarea'; // Removed due to missing module
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, FileText, Upload, Clock, CheckCircle, XCircle, Eye } from 'lucide-vue-next';
import { ref } from 'vue';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/student/dashboard' },
  { title: 'Excuse Requests', href: '#' }
];

// Form state
const excuseForm = ref({
  classId: '',
  date: '',
  reason: '',
  description: '',
  attachment: null as File | null
});

const isDialogOpen = ref(false);

// Available classes
const classes = ref([
  { id: 'CS101', name: 'Computer Science 101' },
  { id: 'MATH201', name: 'Mathematics 201' },
  { id: 'PHYS101', name: 'Physics 101' },
  { id: 'ENG101', name: 'English 101' }
]);

// Excuse requests
const excuseRequests = ref([
  {
    id: 1,
    classId: 'PHYS101',
    className: 'Physics 101',
    date: '2024-09-30',
    reason: 'Medical Appointment',
    description: 'Doctor appointment for routine checkup. Appointment scheduled months in advance.',
    status: 'approved',
    submittedAt: '2024-09-29T14:30:00',
    reviewedAt: '2024-09-29T16:45:00',
    reviewedBy: 'Dr. Michael Johnson',
    attachment: 'medical_certificate.pdf',
    comments: 'Medical certificate verified. Excuse approved.'
  },
  {
    id: 2,
    classId: 'CS101',
    className: 'Computer Science 101',
    date: '2024-09-28',
    reason: 'Family Emergency',
    description: 'Urgent family matter required immediate attention.',
    status: 'pending',
    submittedAt: '2024-09-28T08:15:00',
    reviewedAt: null,
    reviewedBy: null,
    attachment: null,
    comments: null
  },
  {
    id: 3,
    classId: 'MATH201',
    className: 'Mathematics 201',
    date: '2024-09-25',
    reason: 'Transportation Issue',
    description: 'Bus breakdown on the way to university. Unable to find alternative transportation in time.',
    status: 'rejected',
    submittedAt: '2024-09-25T09:30:00',
    reviewedAt: '2024-09-26T10:00:00',
    reviewedBy: 'Prof. Jane Doe',
    attachment: null,
    comments: 'Insufficient documentation provided. Please provide official notice from transport company for future requests.'
  },
  {
    id: 4,
    classId: 'ENG101',
    className: 'English 101',
    date: '2024-09-20',
    reason: 'Illness',
    description: 'Fever and flu symptoms. Not well enough to attend class.',
    status: 'approved',
    submittedAt: '2024-09-20T07:45:00',
    reviewedAt: '2024-09-20T11:30:00',
    reviewedBy: 'Prof. Sarah Wilson',
    attachment: 'medical_report.pdf',
    comments: 'Medical report confirms illness. Get well soon!'
  }
]);

const getStatusColor = (status: string) => {
  switch (status) {
    case 'approved': return 'default';
    case 'pending': return 'secondary';
    case 'rejected': return 'destructive';
    default: return 'outline';
  }
};

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'approved': return CheckCircle;
    case 'pending': return Clock;
    case 'rejected': return XCircle;
    default: return FileText;
  }
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    excuseForm.value.attachment = target.files[0];
  }
};

const submitExcuseRequest = () => {
  console.log('Submitting excuse request:', excuseForm.value);
  // Here you would implement the actual submission logic
  
  // Reset form and close dialog
  excuseForm.value = {
    classId: '',
    date: '',
    reason: '',
    description: '',
    attachment: null
  };
  isDialogOpen.value = false;
};

const reasonOptions = [
  'Medical Appointment',
  'Illness',
  'Family Emergency',
  'Transportation Issue',
  'Official University Business',
  'Religious Observance',
  'Other'
];
</script>

<template>
  <Head title="Excuse Requests" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Excuse Requests</h1>
          <p class="text-muted-foreground">Submit and track your absence excuse requests</p>
        </div>
        
        <!-- New Request Button -->
        <Dialog v-model:open="isDialogOpen">
          <DialogTrigger as-child>
            <Button>
              <Plus class="mr-2 h-4 w-4" />
              New Request
            </Button>
          </DialogTrigger>
          <DialogContent class="sm:max-w-md">
            <DialogHeader>
              <DialogTitle>Submit Excuse Request</DialogTitle>
              <DialogDescription>
                Provide details about your absence and the reason for your excuse request.
              </DialogDescription>
            </DialogHeader>
            
            <div class="space-y-4">
              <!-- Class Selection -->
              <div class="space-y-2">
                <Label for="class">Class</Label>
                <Select v-model="excuseForm.classId">
                  <SelectTrigger>
                    <SelectValue placeholder="Select a class" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id">
                      {{ cls.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Date -->
              <div class="space-y-2">
                <Label for="date">Absence Date</Label>
                <Input 
                  id="date"
                  v-model="excuseForm.date" 
                  type="date" 
                />
              </div>

              <!-- Reason -->
              <div class="space-y-2">
                <Label for="reason">Reason</Label>
                <Select v-model="excuseForm.reason">
                  <SelectTrigger>
                    <SelectValue placeholder="Select a reason" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="reason in reasonOptions" :key="reason" :value="reason">
                      {{ reason }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Description -->
              <div class="space-y-2">
                <Label for="description">Description</Label>
                <textarea
                  id="description"
                  v-model="excuseForm.description"
                  placeholder="Provide additional details about your absence..."
                  rows="3"
                  class="w-full border rounded px-3 py-2 focus:outline-none focus:ring"
                ></textarea>
              </div>

              <!-- File Upload -->
              <div class="space-y-2">
                <Label for="attachment">Supporting Document (Optional)</Label>
                <div class="flex items-center space-x-2">
                  <Input 
                    id="attachment"
                    type="file" 
                    @change="handleFileUpload"
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                    class="cursor-pointer"
                  />
                  <Upload class="h-4 w-4 text-muted-foreground" />
                </div>
                <p class="text-xs text-muted-foreground">
                  Accepted formats: PDF, Image, Word document (Max 5MB)
                </p>
              </div>
            </div>

            <DialogFooter>
              <Button variant="outline" @click="isDialogOpen = false">Cancel</Button>
              <Button @click="submitExcuseRequest">Submit Request</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Requests</CardTitle>
            <FileText class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ excuseRequests.length }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Approved</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">
              {{ excuseRequests.filter(r => r.status === 'approved').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <Clock class="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">
              {{ excuseRequests.filter(r => r.status === 'pending').length }}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Rejected</CardTitle>
            <XCircle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">
              {{ excuseRequests.filter(r => r.status === 'rejected').length }}
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Requests List -->
      <Card>
        <CardHeader>
          <CardTitle>Your Requests</CardTitle>
          <CardDescription>Track the status of your excuse requests</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="request in excuseRequests" :key="request.id" 
                 class="border rounded-lg p-4 space-y-4">
              <!-- Header -->
              <div class="flex items-start justify-between">
                <div>
                  <h3 class="font-medium">{{ request.className }}</h3>
                  <p class="text-sm text-muted-foreground">
                    Absence Date: {{ new Date(request.date).toLocaleDateString() }}
                  </p>
                </div>
                <div class="flex items-center space-x-2">
                  <Badge :variant="getStatusColor(request.status)">
                    <component :is="getStatusIcon(request.status)" class="w-3 h-3 mr-1" />
                    {{ request.status.charAt(0).toUpperCase() + request.status.slice(1) }}
                  </Badge>
                </div>
              </div>

              <!-- Details -->
              <div class="grid gap-3 md:grid-cols-2">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Reason</p>
                  <p class="text-sm">{{ request.reason }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Submitted</p>
                  <p class="text-sm">{{ new Date(request.submittedAt).toLocaleString() }}</p>
                </div>
              </div>

              <!-- Description -->
              <div>
                <p class="text-sm font-medium text-muted-foreground">Description</p>
                <p class="text-sm">{{ request.description }}</p>
              </div>

              <!-- Attachment -->
              <div v-if="request.attachment">
                <p class="text-sm font-medium text-muted-foreground">Attachment</p>
                <div class="flex items-center space-x-2">
                  <FileText class="h-4 w-4" />
                  <span class="text-sm">{{ request.attachment }}</span>
                  <Button variant="ghost" size="sm">
                    <Eye class="h-3 w-3 mr-1" />
                    View
                  </Button>
                </div>
              </div>

              <!-- Review Information -->
              <div v-if="request.status !== 'pending'" class="border-t pt-3">
                <div class="grid gap-3 md:grid-cols-2">
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Reviewed By</p>
                    <p class="text-sm">{{ request.reviewedBy }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-muted-foreground">Reviewed On</p>
                    <p class="text-sm">{{ request.reviewedAt ? new Date(request.reviewedAt).toLocaleString() : 'N/A' }}</p>
                  </div>
                </div>
                <div v-if="request.comments" class="mt-3">
                  <p class="text-sm font-medium text-muted-foreground">Comments</p>
                  <p class="text-sm">{{ request.comments }}</p>
                </div>
              </div>
            </div>

            <!-- No Requests Message -->
            <div v-if="excuseRequests.length === 0" class="text-center py-8">
              <FileText class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
              <h3 class="text-lg font-medium mb-2">No excuse requests</h3>
              <p class="text-muted-foreground mb-4">You haven't submitted any excuse requests yet.</p>
              <Button @click="isDialogOpen = true">
                <Plus class="mr-2 h-4 w-4" />
                Submit Your First Request
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
