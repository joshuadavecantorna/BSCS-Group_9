<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CheckCircle, XCircle, AlertCircle, Calendar, Filter, Search } from 'lucide-vue-next';
import { ref, computed } from 'vue';

// Props from StudentController
interface Props {
  student?: any;
  classes?: any[];
  records?: any;
  selected_class_id?: string | number;
}

const props = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/student/dashboard' },
  { title: 'Attendance History', href: '#' }
];

// Filter states
const filters = ref({
  class: props.selected_class_id?.toString() || '',
  search: '',
  status: '',
  startDate: ''
});

// Use backend data
const attendanceRecords = computed(() => {
  return props.records?.data || [];
});

// Basic client-side filtering for search and class
const filteredRecords = computed(() => {
  const search = (filters.value.search || '').toLowerCase();
  const classId = filters.value.class;
  return attendanceRecords.value.filter((r: any) => {
    const matchesClass = !classId || String(r.class_id) === String(classId);
    const matchesSearch = !search || (r.class_name?.toLowerCase().includes(search));
    return matchesClass && matchesSearch;
  });
});

// Filter function
const applyFilters = () => {
  router.get('/student/attendance-history', {
    class_id: filters.value.class
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

// Summary statistics
const summary = computed(() => {
  const total = attendanceRecords.value.length;
  const present = attendanceRecords.value.filter((r: any) => r.status === 'present').length;
  const absent = attendanceRecords.value.filter((r: any) => r.status === 'absent').length;
  const excused = attendanceRecords.value.filter((r: any) => r.status === 'excused').length;
  
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
    search: '',
    status: '',
    startDate: ''
  };
  applyFilters();
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
                    <div class="font-medium">{{ record.class_name }}</div>
                    <div class="text-sm text-muted-foreground">Session {{ record.session_date }}</div>
                  </div>
                </div>

                <div class="flex items-center space-x-4">
                  <!-- Date -->
                  <div class="text-right">
                    <div class="text-sm font-medium">{{ record.session_date }}</div>
                    <div class="text-xs text-muted-foreground">Start: {{ record.start_time?.slice(11,16) || '—' }}</div>
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
