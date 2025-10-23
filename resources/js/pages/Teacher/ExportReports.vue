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

// Export form
const exportForm = useForm({
  class_id: '',
  start_date: '',
  end_date: '',
  format: 'csv'
});

const exportReport = (type: string) => {
  exportForm.post(`/teacher/reports/export-data`, {
    onSuccess: (response) => {
      // Handle successful export
      alert('Report exported successfully!');
    },
    onError: (errors) => {
      console.error('Export failed:', errors);
    }
  });
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
              <Select v-model="exportForm.class_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Classes</SelectItem>
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
                v-model="exportForm.start_date" 
              />
            </div>

            <div class="grid gap-2">
              <Label for="end-date">End Date</Label>
              <Input 
                id="end-date"
                type="date" 
                v-model="exportForm.end_date" 
              />
            </div>

            <div>
              <Label for="format-select">Format</Label>
              <Select v-model="exportForm.format">
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

            <Button @click="exportReport('attendance')" class="w-full">
              <Download class="h-4 w-4 mr-2" />
              Export Attendance Data
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
              <Select v-model="exportForm.class_id">
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
              <Select v-model="exportForm.format">
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

            <Button @click="exportReport('students')" class="w-full" variant="outline">
              <Download class="h-4 w-4 mr-2" />
              Export Student Data
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
            <Button @click="exportReport('weekly')" variant="outline" class="h-auto p-4">
              <div class="text-center">
                <Calendar class="h-8 w-8 mx-auto mb-2" />
                <div class="font-medium">Weekly Report</div>
                <div class="text-sm text-muted-foreground">Last 7 days attendance</div>
              </div>
            </Button>

            <Button @click="exportReport('monthly')" variant="outline" class="h-auto p-4">
              <div class="text-center">
                <Calendar class="h-8 w-8 mx-auto mb-2" />
                <div class="font-medium">Monthly Report</div>
                <div class="text-sm text-muted-foreground">Current month summary</div>
              </div>
            </Button>

            <Button @click="exportReport('semester')" variant="outline" class="h-auto p-4">
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