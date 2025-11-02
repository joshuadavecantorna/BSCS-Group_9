<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

interface WeeklyAttendance {
  class_name: string;
  course: string;
  student_count: number;
  attendance_rate: number;
  status: 'excellent' | 'good' | 'needs_improvement';
}

interface RecentExport {
  name: string;
  type: string;
  format: string;
  created_at: string;
  file_size: string;
  status: string;
}

interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
    user_id?: number;
  };
  stats: {
    total_reports: number;
    average_attendance: number;
    active_students: number;
    reports_downloaded: number;
  };
  weeklyAttendance: WeeklyAttendance[];
  recentExports: RecentExport[];
  summary: {
    total_classes: number;
    total_students: number;
    attendance_data: WeeklyAttendance[];
  };
}

const props = defineProps<Props>();

// Helper function to get status badge variant
const getStatusVariant = (status: string) => {
  switch (status) {
    case 'excellent':
      return 'default';
    case 'good':
      return 'secondary';
    case 'needs_improvement':
      return 'destructive';
    default:
      return 'outline';
  }
};

// Helper function to get status color for progress bars
const getStatusColor = (status: string) => {
  switch (status) {
    case 'excellent':
      return 'bg-green-600';
    case 'good':
      return 'bg-blue-600';
    case 'needs_improvement':
      return 'bg-yellow-600';
    default:
      return 'bg-gray-600';
  }
};

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' },
  { title: 'Reports', href: '/teacher/reports' }
];
</script>

<template>
  <Head title="Teacher Reports" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Reports & Analytics</h1>
        <p class="text-muted-foreground">
          View detailed reports and analytics for your classes
        </p>
      </div>

      <!-- Report Types -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/reports/attendance')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📊</div>
            <CardTitle>Attendance Reports</CardTitle>
            <CardDescription>Detailed attendance analytics and trends</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button class="w-full">View Attendance</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/reports/students')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">👥</div>
            <CardTitle>Student Reports</CardTitle>
            <CardDescription>Individual student performance and attendance</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Student Analytics</Button>
          </CardContent>
        </Card>

        <Card class="hover:shadow-md transition-shadow cursor-pointer" @click="$inertia.visit('/teacher/reports/export')">
          <CardHeader class="text-center">
            <div class="mx-auto mb-4 text-6xl">📥</div>
            <CardTitle>Export Data</CardTitle>
            <CardDescription>Download reports in various formats</CardDescription>
          </CardHeader>
          <CardContent class="text-center">
            <Button variant="outline" class="w-full">Export Reports</Button>
          </CardContent>
        </Card>
      </div>

      <!-- Quick Reports Summary -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Attendance Summary -->
        <Card>
          <CardHeader>
            <CardTitle>Weekly Attendance Summary</CardTitle>
            <CardDescription>Overview of this week's attendance</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <template v-if="props.weeklyAttendance.length > 0">
                <div v-for="classData in props.weeklyAttendance" :key="classData.class_name" class="space-y-2">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium">{{ classData.class_name }}</span>
                    <div class="flex items-center gap-2">
                      <span class="text-sm text-muted-foreground">{{ classData.attendance_rate }}%</span>
                      <Badge :variant="getStatusVariant(classData.status)">{{ classData.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}</Badge>
                    </div>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div :class="getStatusColor(classData.status)" class="h-2 rounded-full" :style="`width: ${classData.attendance_rate}%`"></div>
                  </div>
                </div>
              </template>
              <div v-else class="text-center py-4 text-muted-foreground">
                No attendance data available
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Activities -->
        <Card>
          <CardHeader>
            <CardTitle>Report Activities</CardTitle>
            <CardDescription>Recent report generation and exports</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <template v-if="props.recentExports.length > 0">
                <div v-for="export_ in props.recentExports" :key="export_.name" class="flex items-center justify-between p-3 border rounded-lg">
                  <div>
                    <p class="font-medium text-sm">{{ export_.name }}</p>
                    <p class="text-xs text-muted-foreground">Generated {{ export_.created_at }} • {{ export_.file_size }}</p>
                  </div>
                  <Badge variant="outline">{{ export_.format }}</Badge>
                </div>
              </template>
              <div v-else class="text-center py-4 text-muted-foreground">
                No recent export activities
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Statistics Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Reports</CardTitle>
            <span class="text-xl">📋</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.total_reports }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Generated this month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Attendance</CardTitle>
            <span class="text-xl">📈</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.average_attendance }}%</div>
            <p class="text-xs text-muted-foreground mt-1">
              Overall attendance rate
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Students</CardTitle>
            <span class="text-xl">👨‍🎓</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.active_students }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Across {{ props.summary.total_classes }} classes
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Reports Downloaded</CardTitle>
            <span class="text-xl">⬇️</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ props.stats.reports_downloaded }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              This month
            </p>
          </CardContent>
        </Card>
      </div>

    </div>
  </AppLayout>
</template>