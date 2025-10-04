<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCircle, XCircle, AlertCircle, Calendar, Filter, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/student/dashboard' },
  { title: 'Attendance History', href: '#' }
];

// Filter states
const filters = ref({
  class: '',
  status: '',
  startDate: '',
  endDate: '',
  search: ''
});

// Attendance records
const attendanceRecords = ref([
  {
    id: 1,
    date: '2024-10-01',
    class: 'Computer Science 101',
    classCode: 'CS101',
    instructor: 'Prof. John Smith',
    status: 'present',
    checkInTime: '09:02 AM',
    checkOutTime: '09:58 AM',
    notes: ''
  },
  {
    id: 2,
    date: '2024-10-01',
    class: 'Mathematics 201',
    classCode: 'MATH201',
    instructor: 'Prof. Jane Doe',
    status: 'present',
    checkInTime: '11:05 AM',
    checkOutTime: '12:28 PM',
    notes: ''
  },
  {
    id: 3,
    date: '2024-09-30',
    class: 'Physics 101',
    classCode: 'PHYS101',
    instructor: 'Dr. Michael Johnson',
    status: 'excused',
    checkInTime: '',
    checkOutTime: '',
    notes: 'Medical appointment - excuse approved'
  },
  {
    id: 4,
    date: '2024-09-30',
    class: 'English 101',
    classCode: 'ENG101',
    instructor: 'Prof. Sarah Wilson',
    status: 'absent',
    checkInTime: '',
    checkOutTime: '',
    notes: 'No excuse provided'
  },
  {
    id: 5,
    date: '2024-09-29',
    class: 'Computer Science 101',
    classCode: 'CS101',
    instructor: 'Prof. John Smith',
    status: 'present',
    checkInTime: '09:00 AM',
    checkOutTime: '09:55 AM',
    notes: ''
  },
  {
    id: 6,
    date: '2024-09-29',
    class: 'Mathematics 201',
    classCode: 'MATH201',
    instructor: 'Prof. Jane Doe',
    status: 'present',
    checkInTime: '11:03 AM',
    checkOutTime: '12:30 PM',
    notes: ''
  },
  {
    id: 7,
    date: '2024-09-27',
    class: 'Physics 101',
    classCode: 'PHYS101',
    instructor: 'Dr. Michael Johnson',
    status: 'absent',
    checkInTime: '',
    checkOutTime: '',
    notes: 'Transportation issue'
  },
  {
    id: 8,
    date: '2024-09-27',
    class: 'English 101',
    classCode: 'ENG101',
    instructor: 'Prof. Sarah Wilson',
    status: 'present',
    checkInTime: '10:02 AM',
    checkOutTime: '10:58 AM',
    notes: ''
  }
]);

// Available classes for filtering
const classes = ref([
  { value: 'CS101', label: 'Computer Science 101' },
  { value: 'MATH201', label: 'Mathematics 201' },
  { value: 'PHYS101', label: 'Physics 101' },
  { value: 'ENG101', label: 'English 101' }
]);

// Filtered records
const filteredRecords = computed(() => {
  return attendanceRecords.value.filter(record => {
    const matchesClass = !filters.value.class || record.classCode === filters.value.class;
    const matchesStatus = !filters.value.status || record.status === filters.value.status;
    const matchesSearch = !filters.value.search || 
      record.class.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      record.instructor.toLowerCase().includes(filters.value.search.toLowerCase());
    
    // Date filtering logic would go here if implemented
    return matchesClass && matchesStatus && matchesSearch;
  });
});

// Summary statistics
const summary = computed(() => {
  const total = filteredRecords.value.length;
  const present = filteredRecords.value.filter(r => r.status === 'present').length;
  const absent = filteredRecords.value.filter(r => r.status === 'absent').length;
  const excused = filteredRecords.value.filter(r => r.status === 'excused').length;
  
  return {
    total,
    present,
    absent,
    excused,
    presentRate: total > 0 ? Math.round((present / total) * 100) : 0
  };
});

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'present': return CheckCircle;
    case 'absent': return XCircle;
    case 'excused': return AlertCircle;
    default: return Calendar;
  }
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'present': return 'default';
    case 'absent': return 'destructive';
    case 'excused': return 'secondary';
    default: return 'outline';
  }
};

const clearFilters = () => {
  filters.value = {
    class: '',
    status: '',
    startDate: '',
    endDate: '',
    search: ''
  };
};
</script>

<template>
  <Head title="Attendance History" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold">Attendance History</h1>
        <p class="text-muted-foreground">View your complete attendance record across all classes</p>
      </div>

      <!-- Summary Cards -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Sessions</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ summary.total }}</div>
          </CardContent>
        </Card>
        
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Present</CardTitle>
            <CheckCircle class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ summary.present }}</div>
            <p class="text-xs text-muted-foreground">{{ summary.presentRate }}% attendance rate</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Absent</CardTitle>
            <XCircle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ summary.absent }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Excused</CardTitle>
            <AlertCircle class="h-4 w-4 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">{{ summary.excused }}</div>
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
          <CardDescription>Filter your attendance records by class, status, or date range</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-5">
            <!-- Search -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Search</label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input 
                  v-model="filters.search" 
                  placeholder="Search classes..."
                  class="pl-10"
                />
              </div>
            </div>

            <!-- Class Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Class</label>
              <Select v-model="filters.class">
                <SelectTrigger>
                  <SelectValue placeholder="All classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All classes</SelectItem>
                  <SelectItem v-for="cls in classes" :key="cls.value" :value="cls.value">
                    {{ cls.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Status Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Status</label>
              <Select v-model="filters.status">
                <SelectTrigger>
                  <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All statuses</SelectItem>
                  <SelectItem value="present">Present</SelectItem>
                  <SelectItem value="absent">Absent</SelectItem>
                  <SelectItem value="excused">Excused</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Start Date -->
            <div class="space-y-2">
              <label class="text-sm font-medium">Start Date</label>
              <Input 
                v-model="filters.startDate" 
                type="date"
              />
            </div>

            <!-- Clear Filters -->
            <div class="space-y-2">
              <label class="text-sm font-medium invisible">Clear</label>
              <Button @click="clearFilters" variant="outline" class="w-full">
                Clear Filters
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Attendance Records -->
      <Card>
        <CardHeader>
          <CardTitle>Attendance Records</CardTitle>
          <CardDescription>{{ filteredRecords.length }} records found</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <!-- Records List -->
            <div class="space-y-3">
              <div v-for="record in filteredRecords" :key="record.id" 
                   class="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors">
                <div class="flex items-center space-x-4">
                  <component :is="getStatusIcon(record.status)" 
                             :class="[
                               'h-5 w-5',
                               record.status === 'present' ? 'text-green-600' :
                               record.status === 'absent' ? 'text-red-600' : 'text-yellow-600'
                             ]" />
                  
                  <div>
                    <div class="font-medium">{{ record.class }}</div>
                    <div class="text-sm text-muted-foreground">{{ record.instructor }}</div>
                  </div>
                </div>

                <div class="flex items-center space-x-4">
                  <!-- Date -->
                  <div class="text-right">
                    <div class="text-sm font-medium">{{ new Date(record.date).toLocaleDateString() }}</div>
                    <div class="text-xs text-muted-foreground">
                      {{ record.checkInTime && record.checkOutTime ? 
                         `${record.checkInTime} - ${record.checkOutTime}` : 
                         'No check-in' }}
                    </div>
                  </div>

                  <!-- Status Badge -->
                  <Badge :variant="getStatusColor(record.status)">
                    {{ record.status.charAt(0).toUpperCase() + record.status.slice(1) }}
                  </Badge>
                </div>
              </div>
            </div>

            <!-- No Records Message -->
            <div v-if="filteredRecords.length === 0" class="text-center py-8">
              <Calendar class="h-12 w-12 text-muted-foreground mx-auto mb-4" />
              <h3 class="text-lg font-medium mb-2">No records found</h3>
              <p class="text-muted-foreground">Try adjusting your filters to see more results.</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
