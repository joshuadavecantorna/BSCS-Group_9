<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import QrScanner from '@/components/QrScanner.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

// Props from controller
interface Props {
  teacher: {
    id: number;
    first_name: string;
    last_name: string;
    department: string;
    position: string;
    email: string;
  };
  stats: {
    totalClasses: number;
    totalStudents: number;
    todayPresent: number;
    todayAbsent: number;
    todayExcused: number;
    todayDropped: number;
    todaySessions: number;
    weeklyAttendanceRate: number;
    monthlyAttendanceRate: number;
  };
  recentActivity?: Array<{
    time: string;
    text: string;
    type: string;
  }>;
}

const props = defineProps<Props>();

const breadcrumbs = [
  { title: 'Teacher Dashboard', href: '/teacher/dashboard' }
];

// QR Scanner state
const showQRScanner = ref(false);

// Computed properties
const teacherName = computed(() => `${props.teacher.first_name} ${props.teacher.last_name}`);

// Mock recent activities if not provided
const recentActivities = props.recentActivity || [
  { time: '9:00 AM', text: 'Started attendance for Introduction to Programming', type: 'info' },
  { time: '8:45 AM', text: 'Updated class schedule for Data Structures', type: 'success' },
  { time: '8:30 AM', text: 'New student enrolled in class', type: 'success' },
  { time: '8:15 AM', text: 'Attendance reminder sent to students', type: 'info' }
];

// Mock weekly attendance data
const weeklyAttendance = [
  { day: 'Mon', present: Math.floor(props.stats.totalStudents * 0.9), absent: Math.floor(props.stats.totalStudents * 0.1) },
  { day: 'Tue', present: Math.floor(props.stats.totalStudents * 0.95), absent: Math.floor(props.stats.totalStudents * 0.05) },
  { day: 'Wed', present: Math.floor(props.stats.totalStudents * 0.88), absent: Math.floor(props.stats.totalStudents * 0.12) },
  { day: 'Thu', present: Math.floor(props.stats.totalStudents * 0.92), absent: Math.floor(props.stats.totalStudents * 0.08) },
  { day: 'Fri', present: Math.floor(props.stats.totalStudents * 0.85), absent: Math.floor(props.stats.totalStudents * 0.15) }
];

const openQRScanner = () => {
  showQRScanner.value = true;
};

const closeQRScanner = () => {
  showQRScanner.value = false;
};

const onScanSuccess = (studentData: any) => {
  console.log('Student scanned successfully:', studentData);
  alert(`Successfully scanned: ${studentData.name} (${studentData.student_id})`);
  closeQRScanner();
};
</script>

<template>
  <Head title="Teacher Dashboard" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto p-6 space-y-6">
      
      <!-- Header Section -->
      <div class="space-y-2">
        <h1 class="text-3xl font-bold tracking-tight">Welcome back, {{ teacherName }}</h1>
        <p class="text-muted-foreground">
          Here's what's happening with your classes today
        </p>
      </div>

      <!-- Quick Stats Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Present Today -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Present Today</CardTitle>
            <span class="text-xl">✅</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.todayPresent }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              <span class="text-emerald-600">+5%</span> from yesterday
            </p>
          </CardContent>
        </Card>

        <!-- Absent Today -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Absent Today</CardTitle>
            <span class="text-xl">❌</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.todayAbsent }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              <span class="text-red-600">-2%</span> from yesterday
            </p>
          </CardContent>
        </Card>

        <!-- Total Classes -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Classes</CardTitle>
            <span class="text-xl">📚</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.totalClasses }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Active classes
            </p>
          </CardContent>
        </Card>

        <!-- Total Students -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Students</CardTitle>
            <span class="text-xl">👥</span>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.totalStudents }}</div>
            <p class="text-xs text-muted-foreground mt-1">
              Across all classes
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Main Content Grid -->
      <div class="grid gap-6 md:grid-cols-7">
        
        <!-- Left Column: Quick Actions & Activity Feed -->
        <div class="space-y-6 md:col-span-3">
          
          <!-- Quick Actions -->
          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
              <CardDescription>Common tasks and shortcuts</CardDescription>
            </CardHeader>
            <CardContent class="space-y-2">
              <Button variant="outline" class="w-full justify-start" size="lg" @click="$inertia.visit('/teacher/classes')">
                <span class="mr-2">➕</span>
                Manage Classes
              </Button>
              
              <Button variant="outline" class="w-full justify-start" size="lg" @click="$inertia.visit('/teacher/classes')">
                <span class="mr-2">📝</span>
                Classes & Attendance
              </Button>
              
              <Button 
                @click="openQRScanner"
                class="w-full justify-start" 
                size="lg"
              >
                <span class="mr-2">📱</span>
                Quick QR Scanner
              </Button>

              <Button variant="outline" class="w-full justify-start" size="lg" @click="$inertia.visit('/teacher/reports')">
                <span class="mr-2">📊</span>
                View Reports
              </Button>
            </CardContent>
          </Card>

          <!-- Recent Activity Feed -->
          <Card>
            <CardHeader>
              <CardTitle>Recent Activity</CardTitle>
              <CardDescription>Latest updates from your classes</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <template v-for="(activity, index) in recentActivities" :key="index">
                  <div class="flex items-start gap-3">
                    <Badge 
                      :variant="activity.type === 'info' ? 'default' : activity.type === 'warning' ? 'outline' : 'secondary'"
                      class="mt-1"
                    >
                      <span v-if="activity.type === 'info'">ℹ️</span>
                      <span v-if="activity.type === 'warning'">⚠️</span>
                      <span v-if="activity.type === 'success'">✓</span>
                    </Badge>
                    <div class="flex-1 space-y-1">
                      <p class="text-sm font-medium leading-none">
                        {{ activity.text }}
                      </p>
                      <p class="text-sm text-muted-foreground">
                        {{ activity.time }}
                      </p>
                    </div>
                  </div>
                  <Separator v-if="index < recentActivities.length - 1" />
                </template>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Right Column: Attendance Chart -->
        <div class="md:col-span-4">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between">
                <div>
                  <CardTitle>Weekly Attendance Overview</CardTitle>
                  <CardDescription>Attendance statistics for this week</CardDescription>
                </div>
                <div class="flex gap-2">
                  <Button variant="default" size="sm">Week</Button>
                  <Button variant="outline" size="sm">Month</Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div v-for="day in weeklyAttendance" :key="day.day" class="space-y-2">
                  <div class="flex items-center justify-between text-sm">
                    <span class="font-medium w-12">{{ day.day }}</span>
                    <span class="text-muted-foreground text-xs">
                      {{ day.present }} present · {{ day.absent }} absent
                    </span>
                  </div>
                  <div class="flex gap-1 h-10 rounded-md overflow-hidden border">
                    <div 
                      class="bg-primary flex items-center justify-center text-primary-foreground text-xs font-medium transition-all hover:opacity-90"
                      :style="{ width: day.present > 0 ? `${(day.present / (day.present + day.absent)) * 100}%` : '0%' }"
                    >
                      {{ day.present > 0 ? day.present : '' }}
                    </div>
                    <div 
                      class="bg-destructive flex items-center justify-center text-destructive-foreground text-xs font-medium transition-all hover:opacity-90"
                      :style="{ width: day.absent > 0 ? `${(day.absent / (day.present + day.absent)) * 100}%` : '0%' }"
                    >
                      {{ day.absent > 0 ? day.absent : '' }}
                    </div>
                  </div>
                </div>
              </div>

              <Separator class="my-4" />

              <!-- Legend -->
              <div class="flex items-center justify-center gap-6 text-sm">
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-primary rounded-sm"></div>
                  <span class="text-muted-foreground">Present</span>
                </div>
                <div class="flex items-center gap-2">
                  <div class="w-3 h-3 bg-destructive rounded-sm"></div>
                  <span class="text-muted-foreground">Absent</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

    </div>

    <!-- QR Scanner Component -->
    <QrScanner 
      :show="showQRScanner"
      @close="closeQRScanner"
      @scan-success="onScanSuccess"
    />
  </AppLayout>
</template>
