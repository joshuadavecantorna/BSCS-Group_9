<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Download, Calendar, FileText } from 'lucide-vue-next';

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
  classes: Array<{
    id: number;
    name: string;
    course: string;
    section: string;
  }>;
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Reports', href: '/teacher/reports' },
  { title: 'Export Reports', href: '/teacher/reports/export' }
];

// Export forms
const attendanceForm = useForm({
  class_id: 'all',
  start_date: '',
  end_date: '',
  format: 'csv',
  export_type: 'attendance'
});

const studentForm = useForm({
  class_id: '',
  format: 'csv',
  export_type: 'students'
});

// Loading states
const isExporting = ref(false);

const exportAttendanceData = () => {
  if (isExporting.value) return;
  
  isExporting.value = true;
  
  // Use regular form submission for file downloads instead of Inertia
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/teacher/reports/export-data';
  form.style.display = 'none';
  
  // Add CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
  }
  
  // Add form data
  const formData = {
    export_type: attendanceForm.export_type,
    format: attendanceForm.format,
    class_id: attendanceForm.class_id === 'all' ? '' : attendanceForm.class_id,
    start_date: attendanceForm.start_date,
    end_date: attendanceForm.end_date
  };
  
  Object.entries(formData).forEach(([key, value]) => {
    if (value) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = value.toString();
      form.appendChild(input);
    }
  });
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  
  isExporting.value = false;
};

const exportStudentData = () => {
  if (isExporting.value) return;
  
  if (!studentForm.class_id) {
    alert('Please select a class first.');
    return;
  }
  
  isExporting.value = true;
  
  // Use regular form submission for file downloads instead of Inertia
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/teacher/reports/export-data';
  form.style.display = 'none';
  
  // Add CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
  }
  
  // Add form data
  const formData = {
    export_type: studentForm.export_type,
    format: studentForm.format,
    class_id: studentForm.class_id
  };
  
  Object.entries(formData).forEach(([key, value]) => {
    if (value) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = value.toString();
      form.appendChild(input);
    }
  });
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  
  isExporting.value = false;
};

const exportQuickReport = (type: 'weekly' | 'monthly' | 'semester') => {
  if (isExporting.value) return;
  
  isExporting.value = true;
  
  // Use regular form submission for file downloads instead of Inertia
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/teacher/reports/export-data';
  form.style.display = 'none';
  
  // Add CSRF token
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
  }
  
  // Add form data
  const formData = {
    export_type: type,
    format: 'csv',
    class_id: ''
  };
  
  Object.entries(formData).forEach(([key, value]) => {
    if (value) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = value.toString();
      form.appendChild(input);
    }
  });
  
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
  
  // Set timeout to reset loading state
  setTimeout(() => {
    isExporting.value = false;
  }, 2000);
};
</script>

<template>
  <Head title="Export Reports" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Export Reports</h1>
        <p class="text-muted-foreground">
          Download detailed reports in various formats
        </p>
      </div>

      <!-- Export Options -->
      <div class="grid gap-6 md:grid-cols-2">
        
        <!-- Attendance Export -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calendar class="h-5 w-5" />
              Attendance Reports
            </CardTitle>
            <CardDescription>
              Export detailed attendance data for your classes
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label for="class-select">Class (Optional)</Label>
              <Select v-model="attendanceForm.class_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Classes</SelectItem>
                  <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id.toString()">
                    {{ cls.name }} ({{ cls.section }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="grid gap-2">
              <Label for="start-date">Start Date</Label>
              <Input 
                id="start-date"
                type="date" 
                v-model="attendanceForm.start_date" 
              />
            </div>

            <div class="grid gap-2">
              <Label for="end-date">End Date</Label>
              <Input 
                id="end-date"
                type="date" 
                v-model="attendanceForm.end_date" 
              />
            </div>

            <div>
              <Label for="format-select">Format</Label>
              <Select v-model="attendanceForm.format">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="csv">CSV</SelectItem>
                  <SelectItem value="xlsx">Excel</SelectItem>
                  <SelectItem value="pdf">PDF</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button 
              @click="exportAttendanceData" 
              class="w-full"
              :disabled="isExporting"
            >
              <Download class="h-4 w-4 mr-2" />
              {{ isExporting ? 'Exporting...' : 'Export Attendance Data' }}
            </Button>
          </CardContent>
        </Card>

        <!-- Student Reports Export -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <FileText class="h-5 w-5" />  
              Student Reports
            </CardTitle>
            <CardDescription>
              Export individual student performance data
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label for="student-class-select">Class</Label>
              <Select v-model="studentForm.class_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select a class" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id.toString()">
                    {{ cls.name }} ({{ cls.section }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label for="student-format-select">Format</Label>
              <Select v-model="studentForm.format">
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="csv">CSV</SelectItem>
                  <SelectItem value="xlsx">Excel</SelectItem>
                  <SelectItem value="pdf">PDF</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <Button 
              @click="exportStudentData" 
              class="w-full" 
              variant="outline"
              :disabled="isExporting"
            >
              <Download class="h-4 w-4 mr-2" />
              {{ isExporting ? 'Exporting...' : 'Export Student Data' }}
            </Button>
          </CardContent>
        </Card>

      </div>

      <!-- Quick Export Options -->
      <Card>
        <CardHeader>
          <CardTitle>Quick Export Options</CardTitle>
          <CardDescription>
            Pre-configured export templates for common reports
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-3">
            <Button 
              @click="exportQuickReport('weekly')" 
              variant="outline" 
              class="h-auto p-4"
              :disabled="isExporting"
            >
              <div class="text-center">
                <Calendar class="h-8 w-8 mx-auto mb-2" />
                <div class="font-medium">Weekly Report</div>
                <div class="text-sm text-muted-foreground">Last 7 days attendance</div>
              </div>
            </Button>

            <Button 
              @click="exportQuickReport('monthly')" 
              variant="outline" 
              class="h-auto p-4"
              :disabled="isExporting"
            >
              <div class="text-center">
                <Calendar class="h-8 w-8 mx-auto mb-2" />
                <div class="font-medium">Monthly Report</div>
                <div class="text-sm text-muted-foreground">Current month summary</div>
              </div>
            </Button>

            <Button 
              @click="exportQuickReport('semester')" 
              variant="outline" 
              class="h-auto p-4"
              :disabled="isExporting"
            >
              <div class="text-center">
                <FileText class="h-8 w-8 mx-auto mb-2" />
                <div class="font-medium">Semester Report</div>
                <div class="text-sm text-muted-foreground">Complete semester data</div>
              </div>
            </Button>
          </div>
        </CardContent>
      </Card>

    </div>
  </AppLayout>
</template>