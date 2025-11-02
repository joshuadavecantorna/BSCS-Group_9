```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button/index';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card/index';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog/index';
import { Input } from '@/components/ui/input/index';
import { Label } from '@/components/ui/label/index';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select/index';
import { Textarea } from '@/components/ui/textarea/index';
import { Badge } from '@/components/ui/badge/index';
import AppLayout from '@/layouts/AppLayout.vue';
import { Plus, CheckCircle, Clock, XCircle, FileText } from 'lucide-vue-next';


const props = defineProps<{ requests: any; classes: any; }>();


const breadcrumbs = [
  { label: 'Dashboard', url: '/dashboard' },
  { label: 'Excuse Requests' }
];


const isDialogOpen = ref(false);


const excuseForm = useForm({
  attendance_session_id: '',
  reason: '',
  attachment: null as File | null,
});

const selectedFile = ref<File | null>(null);


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
    selectedFile.value = target.files[0];
    excuseForm.attachment = target.files[0];
  }
};


const submitExcuseRequest = () => {
  excuseForm.post('/student/excuse-requests', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isDialogOpen.value = false;
      excuseForm.reset();
      selectedFile.value = null;
      router.reload({ only: ['requests'] });
    },
    onError: (errors) => {
      console.error('Submission errors:', errors);
    }
  });
};
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
            
            <form @submit.prevent="submitExcuseRequest" class="space-y-4">
              <!-- Class Selection -->
              <div class="space-y-2">
                <Label for="class">Class</Label>
                <Select v-model:model-value="excuseForm.attendance_session_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Select a class session" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="c in classes"
                      :key="c.id"
                      :value="String(c.id)"
                    >
                      {{ c.name }} - {{ c.course }} - {{ new Date(c.session_date).toLocaleDateString() }} {{ new Date(c.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="excuseForm.errors.attendance_session_id" class="text-sm text-red-600">
                  {{ excuseForm.errors.attendance_session_id }}
                </p>
              </div>


              <!-- Reason -->
              <div class="space-y-2">
                <Label for="reason">Reason</Label>
                <Textarea id="reason" v-model="excuseForm.reason" placeholder="Explain your reason for absence" />
                <p v-if="excuseForm.errors.reason" class="text-sm text-red-600">
                  {{ excuseForm.errors.reason }}
                </p>
              </div>


              <!-- Attachment -->
              <div class="space-y-2">
                <Label for="attachment">Supporting Document (Optional)</Label>
                <Input id="attachment" type="file" @change="handleFileUpload" accept=".pdf,.jpg,.jpeg,.png" />
                <p class="text-xs text-muted-foreground">PDF, JPG, PNG up to 5MB.</p>
                <p v-if="selectedFile" class="text-xs text-green-600">
                  Selected: {{ selectedFile.name }} ({{ (selectedFile.size / 1024 / 1024).toFixed(2) }} MB)
                </p>
                <p v-if="excuseForm.errors.attachment" class="text-sm text-red-600">
                  {{ excuseForm.errors.attachment }}
                </p>
              </div>
              
              <Button type="submit" :disabled="excuseForm.processing">
                {{ excuseForm.processing ? 'Submitting...' : 'Submit Request' }}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>


      <!-- Stats -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Requests</CardTitle>
            <FileText class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ requests.total }}</div>
          </CardContent>
        </Card>


        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Approved</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">
              {{ requests.data.filter(r => r.status === 'approved').length }}
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
              {{ requests.data.filter(r => r.status === 'pending').length }}
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
              {{ requests.data.filter(r => r.status === 'rejected').length }}
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
            <div v-for="request in requests.data" :key="request.id" 
                 class="border rounded-lg p-4 space-y-4">
              <!-- Header -->
              <div class="flex items-start justify-between">
                <div>
                  <h3 class="font-medium">{{ request.attendance_session.class.name }}</h3>
                  <p class="text-sm text-muted-foreground">
                    Absence Date: {{ new Date(request.attendance_session.session_date).toLocaleDateString() }} at {{ new Date(request.attendance_session.start_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
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
                  <p class="text-sm font-medium text-muted-foreground">Submitted On</p>
                  <p class="text-sm">{{ new Date(request.submitted_at).toLocaleString() }}</p>
                </div>
                <div v-if="request.attachment_path">
                  <p class="text-sm font-medium text-muted-foreground">Attachment</p>
                  <a :href="`/storage/${request.attachment_path}`" target="_blank" class="text-sm text-blue-600 hover:underline">View Document</a>
                </div>
              </div>


              <!-- Review Info -->
              <div v-if="request.status !== 'pending'" class="border-t pt-4 mt-4 grid gap-3 md:grid-cols-2">
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Reviewed By</p>
                  <p class="text-sm">{{ request.reviewer ? request.reviewer.name : 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-muted-foreground">Reviewed On</p>
                  <p class="text-sm">{{ request.reviewed_at ? new Date(request.reviewed_at).toLocaleString() : 'N/A' }}</p>
                </div>
                <div v-if="request.review_notes" class="mt-3">
                  <p class="text-sm font-medium text-muted-foreground">Comments</p>
                  <p class="text-sm">{{ request.review_notes }}</p>
                </div>
              </div>
            </div>


            <!-- No Requests Message -->
            <div v-if="requests.data.length === 0" class="text-center py-8">
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
```