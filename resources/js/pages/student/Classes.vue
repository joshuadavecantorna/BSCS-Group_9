<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Clock, MapPin, User, Calendar } from 'lucide-vue-next';
import { ref } from 'vue';

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/student/dashboard' },
  { title: 'Classes', href: '#' }
];

// Student's enrolled classes
const enrolledClasses = ref([
  {
    id: 1,
    name: 'Computer Science 101',
    code: 'CS101',
    instructor: 'Prof. John Smith',
    schedule: 'MWF 9:00-10:00 AM',
    room: 'Room 201',
    totalSessions: 48,
    attendedSessions: 42,
    attendanceRate: 87.5,
    nextClass: '2024-10-02 09:00 AM'
  },
  {
    id: 2,
    name: 'Mathematics 201',
    code: 'MATH201',
    instructor: 'Prof. Jane Doe',
    schedule: 'TTh 11:00 AM-12:30 PM',
    room: 'Room 105',
    totalSessions: 32,
    attendedSessions: 28,
    attendanceRate: 87.5,
    nextClass: '2024-10-03 11:00 AM'
  },
  {
    id: 3,
    name: 'Physics 101',
    code: 'PHYS101',
    instructor: 'Dr. Michael Johnson',
    schedule: 'MWF 2:00-3:30 PM',
    room: 'Lab 301',
    totalSessions: 48,
    attendedSessions: 38,
    attendanceRate: 79.2,
    nextClass: '2024-10-02 02:00 PM'
  },
  {
    id: 4,
    name: 'English 101',
    code: 'ENG101',
    instructor: 'Prof. Sarah Wilson',
    schedule: 'TTh 10:00-11:00 AM',
    room: 'Room 210',
    totalSessions: 32,
    attendedSessions: 30,
    attendanceRate: 93.8,
    nextClass: '2024-10-03 10:00 AM'
  }
]);

const getAttendanceColor = (rate: number) => {
  if (rate >= 90) return 'text-green-600';
  if (rate >= 75) return 'text-yellow-600';
  return 'text-red-600';
};

const getAttendanceBadge = (rate: number) => {
  if (rate >= 90) return 'default';
  if (rate >= 75) return 'secondary';
  return 'destructive';
};
</script>

<template>
  <Head title="My Classes" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header -->
      <div>
        <h1 class="text-3xl font-bold">My Classes</h1>
        <p class="text-muted-foreground">View your enrolled classes and attendance summary</p>
      </div>

      <!-- Classes Grid -->
      <div class="grid gap-6 md:grid-cols-2">
        <Card v-for="classItem in enrolledClasses" :key="classItem.id" class="hover:shadow-md transition-shadow">
          <CardHeader>
            <div class="flex items-start justify-between">
              <div>
                <CardTitle class="text-lg">{{ classItem.name }}</CardTitle>
                <CardDescription class="font-mono text-sm">{{ classItem.code }}</CardDescription>
              </div>
              <Badge :variant="getAttendanceBadge(classItem.attendanceRate)">
                {{ classItem.attendanceRate }}%
              </Badge>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Instructor -->
            <div class="flex items-center space-x-2 text-sm">
              <User class="h-4 w-4 text-muted-foreground" />
              <span>{{ classItem.instructor }}</span>
            </div>

            <!-- Schedule -->
            <div class="flex items-center space-x-2 text-sm">
              <Clock class="h-4 w-4 text-muted-foreground" />
              <span>{{ classItem.schedule }}</span>
            </div>

            <!-- Room -->
            <div class="flex items-center space-x-2 text-sm">
              <MapPin class="h-4 w-4 text-muted-foreground" />
              <span>{{ classItem.room }}</span>
            </div>

            <!-- Next Class -->
            <div class="flex items-center space-x-2 text-sm">
              <Calendar class="h-4 w-4 text-muted-foreground" />
              <span>Next: {{ new Date(classItem.nextClass).toLocaleDateString() }} at {{ new Date(classItem.nextClass).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
            </div>

            <!-- Attendance Summary -->
            <div class="pt-4 border-t">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium">Attendance Rate</span>
                <span class="text-sm font-bold" :class="getAttendanceColor(classItem.attendanceRate)">
                  {{ classItem.attendanceRate }}%
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  class="h-2 rounded-full transition-all duration-300"
                  :class="classItem.attendanceRate >= 90 ? 'bg-green-500' : classItem.attendanceRate >= 75 ? 'bg-yellow-500' : 'bg-red-500'"
                  :style="`width: ${classItem.attendanceRate}%`"
                ></div>
              </div>
              <div class="flex justify-between text-xs text-muted-foreground mt-1">
                <span>{{ classItem.attendedSessions }}/{{ classItem.totalSessions }} sessions</span>
                <span>{{ classItem.totalSessions - classItem.attendedSessions }} missed</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Overall Summary -->
      <Card>
        <CardHeader>
          <CardTitle>Overall Summary</CardTitle>
          <CardDescription>Your attendance across all classes</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-4">
            <div class="text-center">
              <div class="text-2xl font-bold text-blue-600">{{ enrolledClasses.length }}</div>
              <div class="text-sm text-muted-foreground">Total Classes</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">
                {{ Math.round(enrolledClasses.reduce((sum, c) => sum + c.attendanceRate, 0) / enrolledClasses.length) }}%
              </div>
              <div class="text-sm text-muted-foreground">Average Attendance</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-purple-600">
                {{ enrolledClasses.reduce((sum, c) => sum + c.attendedSessions, 0) }}
              </div>
              <div class="text-sm text-muted-foreground">Sessions Attended</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-orange-600">
                {{ enrolledClasses.reduce((sum, c) => sum + (c.totalSessions - c.attendedSessions), 0) }}
              </div>
              <div class="text-sm text-muted-foreground">Sessions Missed</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
