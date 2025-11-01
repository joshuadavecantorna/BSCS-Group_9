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
import { CheckCircle, XCircle, AlertCircle, Calendar, Filter, Search, X } from 'lucide-vue-next';
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
  class: props.selected_class_id?.toString() || 'all',
  search: '',
  status: 'all',
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
    const matchesClass = !classId || classId === 'all' || String(r.class_id) === String(classId);
    const matchesSearch = !search || (r.class_name?.toLowerCase().includes(search));
    return matchesClass && matchesSearch;
  });
});

// Filter function
const applyFilters = () => {
  router.get('/student/attendance-history', {
    class_id: filters.value.class === 'all' ? '' : filters.value.class
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
          <!-- Mobile-responsive filter grid -->
          <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Search - Takes full width on mobile, spans 2 columns on larger screens -->
            <div class="space-y-2 sm:col-span-2 lg:col-span-1">
              <label class="text-sm font-medium text-foreground">Search</label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input 
                  v-model="filters.search" 
                  placeholder="Search classes..."
                  class="pl-10 bg-background border-input hover:border-ring focus:border-ring transition-colors dark:bg-background dark:border-input dark:text-foreground"
                />
              </div>
            </div>

            <!-- Class Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-foreground">Class</label>
              <Select v-model="filters.class">
                <SelectTrigger class="bg-background border-input hover:border-ring focus:border-ring transition-colors dark:bg-background dark:border-input">
                  <SelectValue placeholder="All classes" class="text-foreground" />
                </SelectTrigger>
                <SelectContent class="bg-background border-input dark:bg-background dark:border-input">
                  <SelectItem value="all" class="hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground">All classes</SelectItem>
                  <SelectItem v-for="cls in classes" :key="cls.value" :value="cls.value" class="hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground">
                    {{ cls.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Status Filter -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-foreground">Status</label>
              <Select v-model="filters.status">
                <SelectTrigger class="bg-background border-input hover:border-ring focus:border-ring transition-colors dark:bg-background dark:border-input">
                  <SelectValue placeholder="All statuses" class="text-foreground" />
                </SelectTrigger>
                <SelectContent class="bg-background border-input dark:bg-background dark:border-input">
                  <SelectItem value="all" class="hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground">All statuses</SelectItem>
                  <SelectItem value="present" class="hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground">Present</SelectItem>
                  <SelectItem value="absent" class="hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground">Absent</SelectItem>
                  <SelectItem value="excused" class="hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground">Excused</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Start Date -->
            <div class="space-y-2">
              <label class="text-sm font-medium text-foreground">Start Date</label>
              <Input 
                v-model="filters.startDate" 
                type="date"
                class="bg-background border-input hover:border-ring focus:border-ring transition-colors dark:bg-background dark:border-input dark:text-foreground"
              />
            </div>

            <!-- Clear Filters -->
            <div class="space-y-2">
              <label class="text-sm font-medium invisible">Clear</label>
              <Button @click="clearFilters" variant="outline" class="w-full hover:bg-accent hover:text-accent-foreground transition-colors">
                <X class="h-4 w-4 mr-2" />
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
                   class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border border-border rounded-lg hover:bg-accent/50 transition-colors bg-card">
                <div class="flex items-center space-x-4 mb-3 sm:mb-0">
                  <component :is="getStatusIcon(record.status)" 
                             :class="[
                               'h-5 w-5 flex-shrink-0',
                               record.status === 'present' ? 'text-green-600 dark:text-green-400' :
                               record.status === 'absent' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400'
                             ]" />
                  
                  <div class="min-w-0 flex-1">
                    <div class="font-medium text-foreground truncate">{{ record.class_name }}</div>
                    <div class="text-sm text-muted-foreground">Session {{ record.session_date }}</div>
                  </div>
                </div>

                <div class="flex items-center justify-between sm:justify-end space-x-4">
                  <!-- Date -->
                  <div class="text-left sm:text-right">
                    <div class="text-sm font-medium text-foreground">{{ record.session_date }}</div>
                    <div class="text-xs text-muted-foreground">Start: {{ record.start_time?.slice(11,16) || '—' }}</div>
                  </div>

                  <!-- Status Badge -->
                  <Badge :variant="getStatusColor(record.status)" class="flex-shrink-0">
                    {{ record.status.charAt(0).toUpperCase() + record.status.slice(1) }}
                  </Badge>
                </div>
              </div>
            </div>

            <!-- No Records Message -->
            <div v-if="filteredRecords.length === 0" class="text-center py-12">
              <Calendar class="h-16 w-16 text-muted-foreground mx-auto mb-4" />
              <h3 class="text-lg font-medium text-foreground mb-2">No records found</h3>
              <p class="text-muted-foreground max-w-md mx-auto">Try adjusting your filters to see more results, or check back later for new attendance records.</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
