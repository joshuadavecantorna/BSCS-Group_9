<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 p-4 pt-4">
      <div class="flex h-full flex-1 flex-col gap-6">
      
      <!-- Page Header -->
      <div>
        <h1 class="text-3xl font-bold">Attendance Reports</h1>
        <p class="text-muted-foreground">Generate and export comprehensive attendance reports</p>
      </div>

      <!-- Summary Stats -->
      <div class="grid gap-4 md:grid-cols-5">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <Users class="h-5 w-5 text-blue-500" />
              <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Total Records</span>
            <div class="text-2xl font-bold mt-1">{{ props.summaryStats?.total || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <UserCheck class="h-5 w-5 text-green-500" />
              <div class="w-2 h-2 bg-green-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Present</span>
            <div class="text-2xl font-bold mt-1 text-green-600">{{ props.summaryStats?.present || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <UserX class="h-5 w-5 text-red-500" />
              <div class="w-2 h-2 bg-red-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Absent</span>
            <div class="text-2xl font-bold mt-1 text-red-600">{{ props.summaryStats?.absent || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <AlertTriangle class="h-5 w-5 text-yellow-500" />
              <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Excused</span>
            <div class="text-2xl font-bold mt-1 text-yellow-600">{{ props.summaryStats?.excused || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <Clock class="h-5 w-5 text-orange-500" />
              <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Late</span>
            <div class="text-2xl font-bold mt-1 text-orange-600">{{ props.summaryStats?.late || 0 }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Student Statistics -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <Users class="h-5 w-5 text-purple-500" />
              <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Total Students</span>
            <div class="text-2xl font-bold mt-1">{{ props.studentStats?.total_students || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <UserCheck class="h-5 w-5 text-emerald-500" />
              <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Active Students</span>
            <div class="text-2xl font-bold mt-1 text-emerald-600">{{ props.studentStats?.active_students || 0 }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium">Students by Course</span>
            </div>
            <div class="space-y-1">
              <div v-for="course in (props.studentStats?.students_by_course || []).slice(0, 3)" :key="course.course" class="flex justify-between text-sm">
                <span>{{ course.course }}</span>
                <span class="font-medium">{{ course.count }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="text-sm font-medium">Students by Year</span>
            </div>
            <div class="space-y-1">
              <div v-for="year in (props.studentStats?.students_by_year || []).slice(0, 3)" :key="year.year" class="flex justify-between text-sm">
                <span>{{ year.year }}</span>
                <span class="font-medium">{{ year.count }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Filters
          </CardTitle>
          <CardDescription>Filter attendance records by class, date range, and status</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
            <div class="space-y-2">
              <Label>Department</Label>
              <Select v-model="filters.department">
                <SelectTrigger>
                  <SelectValue placeholder="All Departments" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="dept in (props.departments || [])" :key="dept" :value="dept">
                    {{ dept }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Teacher</Label>
              <Select v-model="filters.teacher_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Teachers" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="teacher in (props.teachers || [])" :key="teacher.id" :value="teacher.id.toString()">
                    {{ teacher.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Class</Label>
              <Select v-model="filters.class_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="cls in (props.classes || [])" :key="cls.id" :value="cls.id.toString()">
                    {{ cls.name }} ({{ cls.course }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Student</Label>
              <Select v-model="filters.student_id">
                <SelectTrigger>
                  <SelectValue placeholder="All Students" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="student in (props.students || [])" :key="student.id" :value="student.id.toString()">
                    {{ student.name }} ({{ student.student_id }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Course</Label>
              <Select v-model="filters.course">
                <SelectTrigger>
                  <SelectValue placeholder="All Courses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="course in (props.courses || [])" :key="course" :value="course">
                    {{ course }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-6 mt-4">
            <div class="space-y-2">
              <Label>Section</Label>
              <Select v-model="filters.section">
                <SelectTrigger>
                  <SelectValue placeholder="All Sections" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="section in (props.sections || [])" :key="section" :value="section">
                    {{ section }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Year</Label>
              <Select v-model="filters.year">
                <SelectTrigger>
                  <SelectValue placeholder="All Years" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="year in (props.years || [])" :key="year" :value="year">
                    {{ year }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Start Date</Label>
              <Input type="date" v-model="filters.date_from" />
            </div>
            <div class="space-y-2">
              <Label>End Date</Label>
              <Input type="date" v-model="filters.date_to" />
            </div>
            <div class="space-y-2">
              <Label>Status</Label>
              <Select v-model="filters.status">
                <SelectTrigger>
                  <SelectValue placeholder="All Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="present">Present</SelectItem>
                  <SelectItem value="absent">Absent</SelectItem>
                  <SelectItem value="excused">Excused</SelectItem>
                  <SelectItem value="late">Late</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>&nbsp;</Label>
              <div class="space-y-2">
                <Button @click="applyFilters" class="w-full">
                  Apply Filters
                </Button>
                <Button variant="outline" @click="clearFilters" class="w-full">
                  Clear Filters
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Export Actions -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Download class="h-5 w-5" />
            Export & Share
          </CardTitle>
          <CardDescription>Download reports or send via email</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-3">
            <Button @click="exportToExcel" class="flex items-center gap-2">
              <FileText class="h-4 w-4" />
              Export to Excel
            </Button>
            <Button @click="exportToPDF" variant="outline" class="flex items-center gap-2">
              <FileText class="h-4 w-4" />
              Export to PDF
            </Button>
            <Button @click="openEmailDialog" variant="outline" class="flex items-center gap-2">
              <Mail class="h-4 w-4" />
              Email Report
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Attendance Table -->
      <Card>
        <CardHeader>
          <CardTitle>Attendance Records</CardTitle>
          <CardDescription>{{ filteredData.length }} records found</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b">
                  <th 
                    v-for="column in [
                      { key: 'student_name', label: 'Student' },
                      { key: 'student_course', label: 'Course' },
                      { key: 'class_name', label: 'Class' },
                      { key: 'teacher_name', label: 'Teacher' },
                      { key: 'date', label: 'Date' },
                      { key: 'status', label: 'Status' },
                      { key: 'time_in', label: 'Time In' }
                    ]"
                    :key="column.key"
                    @click="handleSort(column.key as keyof AttendanceRecord)"
                    class="px-4 py-3 text-left font-medium text-sm cursor-pointer hover:bg-gray-50 transition-colors"
                  >
                    <div class="flex items-center gap-2">
                      {{ column.label }}
                      <component 
                        v-if="getSortIcon(column.key as keyof AttendanceRecord)" 
                        :is="getSortIcon(column.key as keyof AttendanceRecord)" 
                        class="h-4 w-4" 
                      />
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="record in filteredData" :key="record.id" class="border-b hover:bg-gray-50">
                  <td class="px-4 py-3">
                    <div>
                      <div class="font-medium">{{ record.student_name }}</div>
                      <div class="text-sm text-muted-foreground">{{ record.student_id }}</div>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <div>
                      <div class="font-medium">{{ record.student_course }}</div>
                      <div class="text-sm text-muted-foreground">{{ record.student_section }} - {{ record.student_year }}</div>
                    </div>
                  </td>
                  <td class="px-4 py-3">{{ record.class_name }}</td>
                  <td class="px-4 py-3">{{ record.teacher_name }}</td>
                  <td class="px-4 py-3">{{ record.date }}</td>
                  <td class="px-4 py-3">
                    <Badge :class="getStatusColor(record.status)">
                      {{ record.status.charAt(0).toUpperCase() + record.status.slice(1) }}
                    </Badge>
                  </td>
                  <td class="px-4 py-3">{{ record.time_in }}</td>
                </tr>
                <tr v-if="filteredData.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                    No records found matching your filters.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
      </div>
    </div>

    <!-- Email Report Dialog -->
    <Dialog v-model:open="showEmailDialog">
      <DialogContent class="sm:max-w-[425px] bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
        <DialogHeader>
          <DialogTitle class="text-gray-900 dark:text-gray-100">Email Report</DialogTitle>
          <DialogDescription class="text-gray-600 dark:text-gray-400">
            Send the attendance report to an email address as a PDF attachment.
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="email">Email Address *</Label>
            <Input 
              id="email"
              type="email" 
              v-model="emailForm.email" 
              placeholder="Enter email address"
              :class="{ 'border-red-500': emailForm.errors.email }"
            />
            <div v-if="emailForm.errors.email" class="text-sm text-red-600">
              {{ emailForm.errors.email }}
            </div>
          </div>
          
          <div class="text-sm text-gray-800 dark:text-gray-200">
            <p class="font-semibold text-gray-900 dark:text-gray-100">Current Filters:</p>
            <ul class="list-disc list-inside space-y-1 mt-2 text-gray-700 dark:text-gray-300">
              <li v-if="filters.department">Department: {{ filters.department }}</li>
              <li v-if="filters.teacher_id">Teacher: {{ props.teachers?.find(t => t.id.toString() === filters.teacher_id)?.name }}</li>
              <li v-if="filters.class_id">Class: {{ props.classes?.find(c => c.id.toString() === filters.class_id)?.name }}</li>
              <li v-if="filters.date_from">From: {{ filters.date_from }}</li>
              <li v-if="filters.date_to">To: {{ filters.date_to }}</li>
              <li v-if="filters.status">Status: {{ filters.status }}</li>
              <li v-if="!Object.values(filters).some(Boolean)">No filters applied (all records)</li>
            </ul>
          </div>
          
          <div class="text-sm bg-blue-50 dark:bg-blue-900 p-3 rounded border">
            <p class="text-gray-900 dark:text-gray-100"><span class="font-semibold">{{ props.summaryStats?.total || 0 }}</span> records will be included in the report.</p>
          </div>
        </div>

        <DialogFooter>
          <Button type="button" variant="outline" @click="showEmailDialog = false">
            Cancel
          </Button>
          <Button type="submit" @click="sendEmailReport" :disabled="emailForm.processing">
            <span v-if="emailForm.processing">Sending...</span>
            <span v-else>Send Report</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Calendar, FileText, Mail, Download, Filter, ChevronUp, ChevronDown, Users, UserCheck, UserX, Clock, AlertTriangle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Props from AdminController
interface Props {
  summaryStats: {
    total: number;
    present: number;
    absent: number;
    excused: number;
    late: number;
  };
  studentStats: {
    total_students: number;
    active_students: number;
    students_by_course: Array<{
      course: string;
      count: number;
    }>;
    students_by_year: Array<{
      year: string;
      count: number;
    }>;
  };
  attendanceRecords: {
    data: Array<{
      id: number;
      student_name: string;
      student_number: string;
      student_course: string;
      student_section: string;
      student_year: string;
      student_email: string;
      class_name: string;
      class_course: string;
      teacher_first_name: string;
      teacher_last_name: string;
      department: string;
      session_date: string;
      start_time: string;
      end_time: string;
      session_name: string;
      status: 'present' | 'absent' | 'excused' | 'late';
      marked_at: string;
    }>;
    links: any;
    meta: any;
  };
  departments: string[];
  teachers: Array<{
    id: number;
    name: string;
    department: string;
  }>;
  classes: Array<{
    id: number;
    name: string;
    course: string;
    teacher: string;
  }>;
  students: Array<{
    id: number;
    name: string;
    student_id: string;
    course: string;
    section: string;
    year: string;
  }>;
  courses: string[];
  sections: string[];
  years: string[];
  filters: {
    department?: string;
    teacher_id?: string;
    class_id?: string;
    student_id?: string;
    course?: string;
    section?: string;
    year?: string;
    date_from?: string;
    date_to?: string;
    status?: string;
  };
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '#' }
];

// Filter states - initialize with current filters from props
const filters = ref({
  department: props.filters?.department || '',
  teacher_id: props.filters?.teacher_id || '',
  class_id: props.filters?.class_id || '',
  student_id: props.filters?.student_id || '',
  course: props.filters?.course || '',
  section: props.filters?.section || '',
  year: props.filters?.year || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  status: props.filters?.status || ''
});

// Email form for sending reports
const emailForm = useForm({
  email: '',
  department: '',
  teacher_id: '',
  class_id: '',
  date_from: '',
  date_to: '',
  status: ''
});

const showEmailDialog = ref(false);

// Available classes from props
const classes = computed(() => props.classes || []);

// Interface for formatted attendance record
interface AttendanceRecord {
  id: number;
  student_name: string;
  student_id: string;
  class_name: string;
  course: string;
  teacher_name: string;
  department: string;
  date: string;
  time_in: string;
  time_out: string;
  status: string;
  marked_at: string;
}

// Table sorting
interface SortConfig {
  key: keyof AttendanceRecord;
  direction: 'asc' | 'desc';
}

const sortConfig = ref<SortConfig>({ key: 'student_name', direction: 'asc' });

// Use real attendance data from props
const attendanceData = computed(() => props.attendanceRecords?.data || []);

// Formatted data for display
const filteredData = computed(() => {
  if (!attendanceData.value || !Array.isArray(attendanceData.value)) {
    return [];
  }
  return attendanceData.value.map(record => ({
    id: record.id,
    student_name: record.student_name, // This field is already combined from the query
    student_id: record.student_number,
    student_course: record.student_course,
    student_section: record.student_section,
    student_year: record.student_year,
    class_name: record.class_name,
    class_course: record.class_course,
    teacher_name: `${record.teacher_first_name} ${record.teacher_last_name}`,
    department: record.department,
    date: record.session_date,
    time_in: record.start_time,
    time_out: record.end_time,
    status: record.status,
    marked_at: record.marked_at
  }));
});

// Functions
const handleSort = (key: keyof AttendanceRecord) => {
  if (sortConfig.value.key === key) {
    sortConfig.value.direction = sortConfig.value.direction === 'asc' ? 'desc' : 'asc';
  } else {
    sortConfig.value.key = key;
    sortConfig.value.direction = 'asc';
  }
};

const applyFilters = () => {
  router.get('/admin/reports', filters.value, {
    preserveState: true,
    preserveScroll: true,
    only: ['attendanceRecords', 'summaryStats']
  });
};

const clearFilters = () => {
  filters.value = {
    department: '',
    teacher_id: '',
    class_id: '',
    student_id: '',
    course: '',
    section: '',
    year: '',
    date_from: '',
    date_to: '',
    status: ''
  };
  router.get('/admin/reports', {}, {
    preserveState: true,
    preserveScroll: true,
    only: ['attendanceRecords', 'summaryStats']
  });
};

const exportToExcel = () => {
  // Create CSV content from filtered data
  const headers = ['Student Name', 'Student ID', 'Class', 'Course', 'Teacher', 'Department', 'Date', 'Status', 'Time In', 'Time Out', 'Marked At'];
  const csvContent = [
    headers.join(','),
    ...filteredData.value.map(record => [
      `"${record.student_name}"`,
      `"${record.student_id}"`,
      `"${record.class_name}"`,
      `"${record.class_course}"`,
      `"${record.teacher_name}"`,
      `"${record.department}"`,
      `"${record.date}"`,
      `"${record.status}"`,
      `"${record.time_in}"`,
      `"${record.time_out}"`,
      `"${record.marked_at || 'N/A'}"`
    ].join(','))
  ].join('\n');

  // Create and download file
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  if (link.download !== undefined) {
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `attendance-report-${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
};

const exportToPDF = () => {
  // Create a form for PDF download with proper CSRF token
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/admin/reports/export-pdf';
  form.target = '_blank'; // Open in new tab for download
  
  // Add CSRF token from meta tag (now properly included)
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  
  if (csrfToken) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
  }
  
  // Add filter parameters
  Object.entries(filters.value).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = key;
      input.value = value.toString();
      form.appendChild(input);
    }
  });
  
  // Submit form and clean up
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
};

const openEmailDialog = () => {
  // Copy current filters to email form
  emailForm.department = filters.value.department;
  emailForm.teacher_id = filters.value.teacher_id;
  emailForm.class_id = filters.value.class_id;
  emailForm.date_from = filters.value.date_from;
  emailForm.date_to = filters.value.date_to;
  emailForm.status = filters.value.status;
  showEmailDialog.value = true;
};

const sendEmailReport = () => {
  emailForm.post('/admin/reports/email', {
    onSuccess: () => {
      showEmailDialog.value = false;
      emailForm.reset();
    },
    onError: (errors) => {
      console.error('Email send errors:', errors);
    }
  });
};

const getStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'present': return 'bg-green-100 text-green-800';
    case 'absent': return 'bg-red-100 text-red-800';
    case 'excused': return 'bg-yellow-100 text-yellow-800';
    case 'late': return 'bg-orange-100 text-orange-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const getSortIcon = (key: keyof AttendanceRecord) => {
  if (sortConfig.value.key !== key) return null;
  return sortConfig.value.direction === 'asc' ? ChevronUp : ChevronDown;
};
</script>

