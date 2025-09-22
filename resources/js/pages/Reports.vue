<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      
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
            <div class="text-2xl font-bold mt-1">{{ summaryStats.total }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <UserCheck class="h-5 w-5 text-green-500" />
              <div class="w-2 h-2 bg-green-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Present</span>
            <div class="text-2xl font-bold mt-1 text-green-600">{{ summaryStats.present }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <UserX class="h-5 w-5 text-red-500" />
              <div class="w-2 h-2 bg-red-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Absent</span>
            <div class="text-2xl font-bold mt-1 text-red-600">{{ summaryStats.absent }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <AlertTriangle class="h-5 w-5 text-yellow-500" />
              <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Excused</span>
            <div class="text-2xl font-bold mt-1 text-yellow-600">{{ summaryStats.excused }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-2">
              <Clock class="h-5 w-5 text-orange-500" />
              <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
            </div>
            <span class="text-sm font-medium">Late</span>
            <div class="text-2xl font-bold mt-1 text-orange-600">{{ summaryStats.late }}</div>
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
          <div class="grid gap-4 md:grid-cols-4 lg:grid-cols-5">
            <div class="space-y-2">
              <Label>Class</Label>
              <Select v-model="filters.class">
                <SelectTrigger>
                  <SelectValue placeholder="All Classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Classes</SelectItem>
                  <SelectItem v-for="cls in classes" :key="cls.id" :value="cls.id">
                    {{ cls.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>Start Date</Label>
              <Input type="date" v-model="filters.startDate" />
            </div>
            <div class="space-y-2">
              <Label>End Date</Label>
              <Input type="date" v-model="filters.endDate" />
            </div>
            <div class="space-y-2">
              <Label>Status</Label>
              <Select v-model="filters.status">
                <SelectTrigger>
                  <SelectValue placeholder="All Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Status</SelectItem>
                  <SelectItem value="present">Present</SelectItem>
                  <SelectItem value="absent">Absent</SelectItem>
                  <SelectItem value="excused">Excused</SelectItem>
                  <SelectItem value="late">Late</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-2">
              <Label>&nbsp;</Label>
              <Button variant="outline" @click="clearFilters" class="w-full">
                Clear Filters
              </Button>
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
            <Button @click="emailReport" variant="outline" class="flex items-center gap-2">
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
                      { key: 'student_name', label: 'Student Name' },
                      { key: 'student_id', label: 'Student ID' },
                      { key: 'class_name', label: 'Class' },
                      { key: 'date', label: 'Date' },
                      { key: 'status', label: 'Status' },
                      { key: 'time_in', label: 'Time In' },
                      { key: 'time_out', label: 'Time Out' }
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
                  <td class="px-4 py-3 font-medium">{{ record.student_name }}</td>
                  <td class="px-4 py-3 text-muted-foreground">{{ record.student_id }}</td>
                  <td class="px-4 py-3">{{ record.class_name }}</td>
                  <td class="px-4 py-3">{{ record.date }}</td>
                  <td class="px-4 py-3">
                    <Badge :class="getStatusColor(record.status)">
                      {{ record.status }}
                    </Badge>
                  </td>
                  <td class="px-4 py-3">{{ record.time_in }}</td>
                  <td class="px-4 py-3">{{ record.time_out }}</td>
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
  </AppLayout>
</template>

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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, FileText, Mail, Download, Filter, ChevronUp, ChevronDown, Users, UserCheck, UserX, Clock, AlertTriangle } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: dashboard().url },
  { title: 'Reports', href: '#' }
];

// Filter states
const filters = ref({
  class: '',
  startDate: '',
  endDate: '',
  status: ''
});

// Available classes
const classes = ref([
  { id: '1', name: 'BSCS-A' },
  { id: '2', name: 'BSIT-B' },
  { id: '3', name: 'BSCS-C' },
  { id: '4', name: 'BSIT-D' }
]);

// Table sorting
interface SortConfig {
  key: keyof AttendanceRecord;
  direction: 'asc' | 'desc';
}

const sortConfig = ref<SortConfig>({ key: 'student_name', direction: 'asc' });

// Sample attendance data
interface AttendanceRecord {
  id: number;
  student_name: string;
  student_id: string;
  class_name: string;
  date: string;
  status: 'Present' | 'Absent' | 'Excused' | 'Late';
  time_in: string;
  time_out: string;
}

const attendanceData = ref<AttendanceRecord[]>([
  { id: 1, student_name: 'Juan Dela Cruz', student_id: 'STU-2024-001', class_name: 'BSCS-A', date: '2025-09-22', status: 'Present', time_in: '08:00 AM', time_out: '09:00 AM' },
  { id: 2, student_name: 'Maria Santos', student_id: 'STU-2024-002', class_name: 'BSCS-A', date: '2025-09-22', status: 'Absent', time_in: '-', time_out: '-' },
  { id: 3, student_name: 'Pedro Garcia', student_id: 'STU-2024-003', class_name: 'BSIT-B', date: '2025-09-22', status: 'Late', time_in: '08:15 AM', time_out: '09:15 AM' },
  { id: 4, student_name: 'Ana Rodriguez', student_id: 'STU-2024-004', class_name: 'BSCS-A', date: '2025-09-22', status: 'Excused', time_in: '-', time_out: '-' },
  { id: 5, student_name: 'Jose Martinez', student_id: 'STU-2024-005', class_name: 'BSIT-B', date: '2025-09-22', status: 'Present', time_in: '07:55 AM', time_out: '08:55 AM' },
]);

// Computed filtered and sorted data
const filteredData = computed(() => {
  let filtered = attendanceData.value;

  if (filters.value.class) {
    filtered = filtered.filter(record => 
      record.class_name === classes.value.find(c => c.id === filters.value.class)?.name
    );
  }

  if (filters.value.status) {
    filtered = filtered.filter(record => 
      record.status.toLowerCase() === filters.value.status.toLowerCase()
    );
  }

  if (filters.value.startDate) {
    filtered = filtered.filter(record => record.date >= filters.value.startDate);
  }

  if (filters.value.endDate) {
    filtered = filtered.filter(record => record.date <= filters.value.endDate);
  }

  // Sort data
  return filtered.sort((a, b) => {
    const aVal = a[sortConfig.value.key];
    const bVal = b[sortConfig.value.key];
    
    if (aVal === bVal) return 0;
    
    const result = aVal > bVal ? 1 : -1;
    return sortConfig.value.direction === 'asc' ? result : -result;
  });
});

// Summary statistics
const summaryStats = computed(() => {
  const data = filteredData.value;
  return {
    total: data.length,
    present: data.filter(r => r.status === 'Present').length,
    absent: data.filter(r => r.status === 'Absent').length,
    excused: data.filter(r => r.status === 'Excused').length,
    late: data.filter(r => r.status === 'Late').length,
  };
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

const clearFilters = () => {
  filters.value = {
    class: '',
    startDate: '',
    endDate: '',
    status: ''
  };
};

const exportToExcel = () => {
  // TODO: Implement Excel export
  console.log('Exporting to Excel...', filteredData.value);
  alert('Excel export feature will be implemented soon!');
};

const exportToPDF = () => {
  // TODO: Implement PDF export
  console.log('Exporting to PDF...', filteredData.value);
  alert('PDF export feature will be implemented soon!');
};

const emailReport = () => {
  // TODO: Implement email functionality
  console.log('Emailing report...', filteredData.value);
  alert('Email report feature will be implemented soon!');
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'Present': return 'bg-green-100 text-green-800';
    case 'Absent': return 'bg-red-100 text-red-800';
    case 'Excused': return 'bg-yellow-100 text-yellow-800';
    case 'Late': return 'bg-orange-100 text-orange-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const getSortIcon = (key: keyof AttendanceRecord) => {
  if (sortConfig.value.key !== key) return null;
  return sortConfig.value.direction === 'asc' ? ChevronUp : ChevronDown;
};
</script>

